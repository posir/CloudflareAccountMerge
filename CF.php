<?php
@mkdir("zone",0755);
@mkdir("record",0755);
include "CF_config.php";

// 检查命令行参数
if ($argc < 2) {
    echo "\n\nCommand: php {$argv[0]} \n";
    echo "Available Command:\n";
    echo "  php {$argv[0]} get_domains             Export OLD Cloudflare Account Domain Lists \n";
    echo "  php {$argv[0]} list_domains            Check OLD Cloudflare Account Domain list \n";
    echo "  php {$argv[0]} export_record           Export OLD Cloudflare Account Domain DNS Record \n";
    echo "  php {$argv[0]} add_domain              Add Domain to New Cloudflare Account \n";
    echo "  php {$argv[0]} delete_domain           Delete Domain on New Cloudflare Account \n";
    echo "  php {$argv[0]} import_record           Import Domain to New Cloudflare Account \n";
    echo "  php {$argv[0]} clear                   Delete All Cache Data \n\n";
    exit(1);
}

$command = $argv[1];
switch ($command) {
    case 'get_domains':
        $domains = getDomainList($O['email'], $O['api_key']);
        foreach ($domains as $k => $v) {
            file_put_contents("zone/{$k}.dat",$v);
            echo "  Domain: {$k}, Zoneid: {$v} ".PHP_EOL;
        }
        echo "[Success] Domain lists has export, check use ls zone ".PHP_EOL;
        break;

    case 'list_domains':
        $files = array_diff(scandir("zone"), array('.', '..'));
        foreach ($files as $file) {
            $Domain = basename($file,".dat");
            $ZoneId = file_get_contents("zone/".$file);
            echo "{$Domain} => {$ZoneId}".PHP_EOL;
        }
        echo "[Success] Domain lists".PHP_EOL;
        break;

    case 'export_record':
        // 读取域名列表
        $files = array_diff(scandir("zone"), array('.', '..'));
        foreach ($files as $file) {
            $Domain = basename($file,".dat");
            $ZoneId = file_get_contents("zone/".$file);
            echo "  {$Domain} => {$ZoneId} Record has been export".PHP_EOL;
            // 读取记录
            $records = getDnsRecords($O['api_key'],$O['email'],  $ZoneId);
            file_put_contents("record/{$Domain}.dat", $records);
        }
        echo "[Success] All Domain Record has been export".PHP_EOL;
        break;

    case 'add_domain':
        // 读取域名列表
        $files = array_diff(scandir("zone"), array('.', '..'));
        foreach ($files as $file) {
            $Domain = basename($file,".dat");
            $response = addDomain($N['email'], $N['api_key'], $Domain);
            if ($response->success) {
                $zoneid = $response->result->id;
                $orgreg = $response->result->original_registrar;
                $nsRecords = $response->result->name_servers;
                echo "Domain $Domain Added... \n";
                echo "Please Change NS Server To \n";
                foreach ($nsRecords as $nsRecord) {
                    echo "- " . $nsRecord . "\n";
                }
            } else {
                echo "$Domain add fail...," . $response->errors[0]->message . "\n";
            }
        }
        break;

    case 'delete_domain':
        // 读取域名列表
        $files = array_diff(scandir("zone"), array('.', '..'));
        foreach ($files as $file) {
            $Domain = basename($file,".dat");
            $ZoneId = getZoneId($N['api_key'], $N['email'],  $Domain);
            $response = deleteDomain($N['api_key'], $N['email'], $ZoneId);
            echo "Delete Success".PHP_EOL;
        }
        break;

    case 'import_record':
        // 读取域名列表
        $files = array_diff(scandir("zone"), array('.', '..'));
        foreach ($files as $file) {
            $Domain = basename($file,".dat");
            $ZoneId = getZoneId($N['api_key'], $N['email'],  $Domain);
            $records = file("record/{$Domain}.dat", FILE_IGNORE_NEW_LINES);
            foreach ($records as $record) {
                $parts = explode(',', $record);
                $recordsData = [
                    'type' => trim($parts[0]),
                    'name' => trim($parts[1]),
                    'content' => trim($parts[2]),
                    'proxied' => trim($parts[3]) === 'true',
                    'ttl' => 1,
                ];
                echo json_encode($recordsData).PHP_EOL;
                $result = importDnsRecords($N['api_key'], $N['email'], $ZoneId, $recordsData);
//                var_dump($result);
                if ($result->success) {
                    echo "  [Success] $Domain DNS Record import success...".PHP_EOL;
                } else {
                    echo "  [Fail] $Domain DNS Record import fail...".PHP_EOL;
                }
            }
        }
        echo "[Success] All Domain Record has been Restore... ".PHP_EOL;
        break;

    case 'clear':
        $folder_path = ["zone","record"];
        foreach ($folder_path as $item) {
            $files = glob($item.'/*');
            foreach($files as $file) {
                if(is_file($file))  unlink($file);
            }
        }
        echo "[Success] All Cache Data Has been Removed".PHP_EOL;
        break;
    default:
        echo "无效的命令: $command\n";
        exit(1);
}

// get domain zone id
function getZoneId($apiKey, $email, $domainName)
{
    $headers = [
        'Content-Type: application/json',
        'X-Auth-Email: ' . $email,
        'X-Auth-Key: ' . $apiKey
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.cloudflare.com/client/v4/zones?name=' . urlencode($domainName));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response, true);
    if ($result['success'] && count($result['result']) > 0) {
        return $result['result'][0]['id'];
    }

    return false;
}

// get all domain list
function getDomainList($email, $api_key)
{
    $api_url = "https://api.cloudflare.com/client/v4/zones";
    $headers = array(
        "X-Auth-Email: " . $email,
        "X-Auth-Key: " . $api_key,
        "Content-Type: application/json"
    );
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $api_url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($curl);
    curl_close($curl);
    $data = json_decode($response, true);
    $domains = array();
    foreach ($data['result'] as $item) {
        $domains[$item['name']] = $item['id'];
    }
    return $domains;
}

// get domain record
function getDnsRecords($apiKey, $email, $zoneId)
{
    $headers = [
        'Content-Type: application/json',
        'X-Auth-Email: ' . $email,
        'X-Auth-Key: ' . $apiKey
    ];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.cloudflare.com/client/v4/zones/$zoneId/dns_records");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($ch);
    curl_close($ch);
    return formatDnsRecords($response);
}

// format dns record
function formatDnsRecords($response)
{
    $result = json_decode($response, true);
    $records = '';
    if ($result['success']) {
        foreach ($result['result'] as $record) {
            $proxied = $record['proxied'] ? 'true' : 'false';
            $records .= "{$record['type']},{$record['name']},{$record['content']},$proxied\n";
        }
    }
    return $records;
}

// add domain to cloudflare
function addDomain($email, $apiKey, $domainName)
{
    $headers = [
        'Content-Type: application/json',
        'X-Auth-Email: ' . $email,
        'X-Auth-Key: ' . $apiKey
    ];
    $data = [
        'name' => $domainName,
        'jump_start' => true
    ];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.cloudflare.com/client/v4/zones');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response);
}

// import domain record
function importDnsRecords($apiKey, $email, $zoneId, $Record)
{
    $headers = [
        'Content-Type: application/json',
        'X-Auth-Email: ' . $email,
        'X-Auth-Key: ' . $apiKey
    ];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.cloudflare.com/client/v4/zones/$zoneId/dns_records");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($Record));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return json_decode($response);
}

// Delete Domain Zone
function deleteDomain($apiKey, $email, $zoneId)
{
    $headers = [
        'Content-Type: application/json',
        'X-Auth-Email: ' . $email,
        'X-Auth-Key: ' . $apiKey
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.cloudflare.com/client/v4/zones/$zoneId");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($ch);
    var_dump($response);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return json_decode($response);
}

