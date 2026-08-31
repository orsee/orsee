<?php
// part of orsee. see orsee.org

namespace PHPMailer\PHPMailer {
    if (!interface_exists('PHPMailer\\PHPMailer\\OAuthTokenProvider', false)) {
        interface OAuthTokenProvider
        {
            public function getOauth64(): string;
        }
    }
}

namespace {
    class ORSEE_PHPMailer_OAuthTokenProvider implements \PHPMailer\PHPMailer\OAuthTokenProvider
    {
        private $oauth_config;
        private $purpose;

        public function __construct($oauth_config, $purpose = 'smtp_send') {
            $this->oauth_config = $oauth_config;
            $this->purpose = $purpose;
        }

        public function getOauth64(): string {
            return experimentmail__oauth_build_oauth64($this->oauth_config, $this->purpose);
        }
    }

    function experimentmail__mail($recipient,$subject,$message,$headers,$env_sender="") {
        $headers .= "Content-Type: text/plain; charset=\"UTF-8\"\r\n";
        $message=html_entity_decode($message,ENT_COMPAT,'UTF-8');
        $subject=html_entity_decode($subject,ENT_COMPAT,'UTF-8');
        if (!experimentmail__use_phpmailer()) {
            $subject='=?UTF-8?B?'.base64_encode($subject).'?=';
        }
        $done=experimentmail__send($recipient,$subject,$message,$headers,$env_sender);
        return $done;
    }

    function experimentmail__use_phpmailer() {
        global $settings__mail_transport;
        return (isset($settings__mail_transport) && strtolower((string)$settings__mail_transport)=="phpmailer");
    }

    function experimentmail__phpmailer_debug_enabled() {
        global $settings__phpmailer_debug;
        return (isset($settings__phpmailer_debug) && strtolower((string)$settings__phpmailer_debug)=="y");
    }

    function experimentmail__phpmailer_debug_log($line) {
        if (!experimentmail__phpmailer_debug_enabled()) {
            return;
        }
        error_log("ORSEE PHPMailer: ".experimentmail__phpmailer_debug_sanitize((string)$line));
    }

    function experimentmail__phpmailer_debug_sanitize($line) {
        if (!is_string($line) || $line==="") {
            return "";
        }
        $line=preg_replace('/(AUTH XOAUTH2\\s+)[A-Za-z0-9+\\/=\\-_.]+/i','$1[oauth_token_hidden]',$line);
        $line=preg_replace('/(auth=Bearer\\s+)[^\\x01\\r\\n\\s]+/i','$1[oauth_token_hidden]',$line);
        $line=preg_replace('/(refresh_token=)[^&\\s]+/i','$1[oauth_refresh_token_hidden]',$line);
        return $line;
    }

    function experimentmail__phpmailer_smtp_auth_type() {
        global $settings__phpmailer_smtp_auth_type;
        if (isset($settings__phpmailer_smtp_auth_type) && $settings__phpmailer_smtp_auth_type) {
            $type=strtolower(trim((string)$settings__phpmailer_smtp_auth_type));
            if ($type=="oauth2" || $type=="password" || $type=="none") {
                return $type;
            }
        }
        return "none";
    }

    function experimentmail__oauth_provider_defaults($provider,$tenant="common") {
        $provider=strtolower(trim((string)$provider));
        $defaults=array(
            "provider"=>$provider,
            "token_endpoint"=>"",
            "auth_endpoint"=>"",
            "scopes"=>""
        );
        if ($provider=="google") {
            $defaults["token_endpoint"]="https://oauth2.googleapis.com/token";
            $defaults["auth_endpoint"]="https://accounts.google.com/o/oauth2/v2/auth";
            $defaults["scopes"]="https://mail.google.com/";
        } elseif ($provider=="microsoft") {
            $tenant=trim((string)$tenant);
            if ($tenant==="") {
                $tenant="common";
            }
            $defaults["token_endpoint"]="https://login.microsoftonline.com/".$tenant."/oauth2/v2.0/token";
            $defaults["auth_endpoint"]="https://login.microsoftonline.com/".$tenant."/oauth2/v2.0/authorize";
            $defaults["scopes"]="offline_access https://outlook.office.com/SMTP.Send";
        }
        return $defaults;
    }

    function experimentmail__oauth_default_state() {
        return create_random_token("smtp_oauth_state");
    }

    function experimentmail__oauth_authorization_url($oauth_config,$redirect_uri,$state="") {
        if (!is_array($oauth_config)) {
            throw new RuntimeException("OAuth identity config is not valid.");
        }
        $provider=isset($oauth_config['provider']) ? strtolower(trim((string)$oauth_config['provider'])) : "";
        $client_id=isset($oauth_config['client_id']) ? trim((string)$oauth_config['client_id']) : "";
        $auth_endpoint=isset($oauth_config['auth_endpoint']) ? trim((string)$oauth_config['auth_endpoint']) : "";
        $scopes=isset($oauth_config['scopes']) ? trim((string)$oauth_config['scopes']) : "";
        $identity=isset($oauth_config['identity']) ? trim((string)$oauth_config['identity']) : "";

        if ($provider==="") {
            throw new RuntimeException("OAuth provider is missing.");
        }
        if ($client_id==="") {
            throw new RuntimeException("OAuth client id is missing.");
        }
        if ($auth_endpoint==="") {
            throw new RuntimeException("OAuth authorization endpoint is missing.");
        }
        if ($scopes==="") {
            throw new RuntimeException("OAuth scopes are missing.");
        }
        if (!is_string($redirect_uri) || trim($redirect_uri)==="") {
            throw new RuntimeException("OAuth redirect URI is missing.");
        }
        $redirect_uri=trim($redirect_uri);
        if ($state==="") {
            $state=experimentmail__oauth_default_state();
        }

        $pars=array(
            'client_id'=>$client_id,
            'response_type'=>'code',
            'redirect_uri'=>$redirect_uri,
            'scope'=>$scopes,
            'state'=>$state
        );
        if ($provider==="google") {
            $pars['access_type']='offline';
            $pars['prompt']='consent';
            $pars['include_granted_scopes']='true';
        } elseif ($provider==="microsoft") {
            $pars['response_mode']='query';
        }
        if ($identity!=="" && filter_var($identity,FILTER_VALIDATE_EMAIL)) {
            $pars['login_hint']=$identity;
        }
        return $auth_endpoint."?".http_build_query($pars,'','&',PHP_QUERY_RFC3986);
    }

    function experimentmail__oauth_trim($value) {
        return trim((string)$value);
    }

    function experimentmail__oauth_normalize_identity_config($identity_key,$raw) {
        if (!is_array($raw)) {
            $raw=array();
        }
        $provider=isset($raw["provider"]) ? experimentmail__oauth_trim($raw["provider"]) : "";
        if ($provider==="") {
            $provider="google";
        }
        if ($provider==="") {
            $provider="google";
        }
        $tenant=isset($raw["tenant"]) ? experimentmail__oauth_trim($raw["tenant"]) : "";
        if ($tenant==="") {
            $tenant="common";
        }
        $defaults=experimentmail__oauth_provider_defaults($provider,$tenant);

        $identity=isset($raw["identity"]) ? experimentmail__oauth_trim($raw["identity"]) : "";
        if ($identity==="" && is_string($identity_key) && $identity_key!=="*" && filter_var($identity_key,FILTER_VALIDATE_EMAIL)) {
            $identity=$identity_key;
        }

        $cfg=array();
        $cfg["provider"]=$provider;
        $cfg["tenant"]=$tenant;
        $cfg["identity"]=strtolower($identity);
        $cfg["client_id"]=isset($raw["client_id"]) ? experimentmail__oauth_trim($raw["client_id"]) : "";
        $cfg["client_secret"]=isset($raw["client_secret"]) ? experimentmail__oauth_trim($raw["client_secret"]) : "";
        $cfg["token_endpoint"]=isset($raw["token_endpoint"]) ? experimentmail__oauth_trim($raw["token_endpoint"]) : "";
        if ($cfg["token_endpoint"]==="") {
            $cfg["token_endpoint"]=$defaults["token_endpoint"];
        }
        $cfg["auth_endpoint"]=isset($raw["auth_endpoint"]) ? experimentmail__oauth_trim($raw["auth_endpoint"]) : "";
        if ($cfg["auth_endpoint"]==="") {
            $cfg["auth_endpoint"]=$defaults["auth_endpoint"];
        }
        $cfg["scopes"]=isset($raw["scopes"]) ? experimentmail__oauth_trim($raw["scopes"]) : "";
        if ($cfg["scopes"]==="") {
            $cfg["scopes"]=$defaults["scopes"];
        }
        $cfg["refresh_token"]=isset($raw["refresh_token"]) ? experimentmail__oauth_trim($raw["refresh_token"]) : "";
        return $cfg;
    }

    function experimentmail__oauth_identity_map() {
        global $settings__phpmailer_smtp_oauth_identities;
        $map=array();
        if (isset($settings__phpmailer_smtp_oauth_identities) && is_array($settings__phpmailer_smtp_oauth_identities)) {
            foreach ($settings__phpmailer_smtp_oauth_identities as $key=>$raw) {
                $map[strtolower(trim((string)$key))]=experimentmail__oauth_normalize_identity_config($key,$raw);
            }
        }
        if (count($map)<1) {
            $map["*"]=experimentmail__oauth_normalize_identity_config("*",array("provider"=>"google","tenant"=>"common"));
        }
        return $map;
    }

    function experimentmail__oauth_resolve_identity_config($from_address) {
        $from_address=strtolower(trim((string)$from_address));
        $map=experimentmail__oauth_identity_map();
        if (isset($map[$from_address])) {
            return $map[$from_address];
        }
        if (isset($map["*"])) {
            $cfg=$map["*"];
            if ($cfg["identity"]==="") {
                $cfg["identity"]=$from_address;
            }
            return $cfg;
        }
        foreach ($map as $cfg) {
            if (is_array($cfg) && isset($cfg["identity"]) && $cfg["identity"]!=="") {
                return $cfg;
            }
        }
        return false;
    }

    function experimentmail__oauth_tokens__load($purpose,$identity,$provider) {
        if (!experimentmail__oauth_tokens__table_ready()) {
            return false;
        }
        $purpose=trim((string)$purpose);
        $identity=strtolower(trim((string)$identity));
        $provider=strtolower(trim((string)$provider));
        if ($purpose==="" || $identity==="" || $provider==="") {
            return false;
        }
        $pars=array(':purpose'=>$purpose,':identity'=>$identity,':provider'=>$provider);
        $query="SELECT * FROM ".table('oauth_tokens')."
            WHERE purpose= :purpose
            AND identity_email= :identity
            AND provider= :provider";
        $result=or_query($query,$pars);
        if (!$result) {
            return false;
        }
        $line=pdo_fetch_assoc($result);
        if (!is_array($line)) {
            return false;
        }
        return $line;
    }

    function experimentmail__oauth_tokens__save($purpose,$identity,$provider,$refresh_token,$access_token,$expires_at) {
        if (!experimentmail__oauth_tokens__table_ready()) {
            return false;
        }
        $purpose=trim((string)$purpose);
        $identity=strtolower(trim((string)$identity));
        $provider=strtolower(trim((string)$provider));
        if ($purpose==="" || $identity==="" || $provider==="") {
            return false;
        }
        $now=time();
        $existing=experimentmail__oauth_tokens__load($purpose,$identity,$provider);
        if ($existing && isset($existing['token_id'])) {
            $pars=array(
                ':token_id'=>$existing['token_id'],
                ':refresh_token'=>(string)$refresh_token,
                ':access_token'=>(string)$access_token,
                ':access_token_expires_at'=>(int)$expires_at,
                ':updated_at'=>$now
            );
            $query="UPDATE ".table('oauth_tokens')."
                SET refresh_token= :refresh_token,
                    access_token= :access_token,
                    access_token_expires_at= :access_token_expires_at,
                    updated_at= :updated_at
                WHERE token_id= :token_id";
            return or_query($query,$pars);
        }
        $pars=array(
            ':purpose'=>$purpose,
            ':identity'=>$identity,
            ':provider'=>$provider,
            ':refresh_token'=>(string)$refresh_token,
            ':access_token'=>(string)$access_token,
            ':access_token_expires_at'=>(int)$expires_at,
            ':created_at'=>$now,
            ':updated_at'=>$now
        );
        $query="INSERT INTO ".table('oauth_tokens')."
            SET purpose= :purpose,
                identity_email= :identity,
                provider= :provider,
                refresh_token= :refresh_token,
                access_token= :access_token,
                access_token_expires_at= :access_token_expires_at,
                created_at= :created_at,
                updated_at= :updated_at";
        return or_query($query,$pars);
    }

    function experimentmail__oauth_tokens__table_ready() {
        static $checked=false;
        static $ready=false;
        if ($checked) {
            return $ready;
        }
        $checked=true;
        $table_name=table('oauth_tokens');
        $query="SHOW TABLES LIKE ".pdo_escape_string($table_name);
        $result=or_query($query);
        if (!$result) {
            $ready=false;
            return $ready;
        }
        $line=$result->fetch(\PDO::FETCH_NUM);
        $ready=(is_array($line) && isset($line[0]) && $line[0]==$table_name);
        return $ready;
    }

    function experimentmail__oauth_refresh_access_token($oauth_config,$refresh_token) {
        $token_endpoint=isset($oauth_config['token_endpoint']) ? trim((string)$oauth_config['token_endpoint']) : "";
        if ($token_endpoint==="") {
            throw new RuntimeException("OAuth token endpoint is missing.");
        }
        $post=array(
            'grant_type'=>'refresh_token',
            'refresh_token'=>$refresh_token,
            'client_id'=>isset($oauth_config['client_id']) ? (string)$oauth_config['client_id'] : ""
        );
        if (isset($oauth_config['client_secret']) && $oauth_config['client_secret']!=="") {
            $post['client_secret']=(string)$oauth_config['client_secret'];
        }
        if (isset($oauth_config['provider']) && strtolower((string)$oauth_config['provider'])==="microsoft"
            && isset($oauth_config['scopes']) && trim((string)$oauth_config['scopes'])!=="") {
            $post['scope']=trim((string)$oauth_config['scopes']);
        }
        $ch=curl_init($token_endpoint);
        curl_setopt_array($ch,array(
            CURLOPT_POST=>true,
            CURLOPT_POSTFIELDS=>http_build_query($post,'','&'),
            CURLOPT_RETURNTRANSFER=>true,
            CURLOPT_TIMEOUT=>15,
            CURLOPT_HTTPHEADER=>array(
                'Content-Type: application/x-www-form-urlencoded'
            )
        ));
        $raw=curl_exec($ch);
        if ($raw===false) {
            $err=curl_error($ch);
            curl_close($ch);
            throw new RuntimeException("OAuth token request failed: ".$err);
        }
        $code=curl_getinfo($ch,CURLINFO_HTTP_CODE);
        curl_close($ch);
        $json=json_decode($raw,true);
        if ($code<200 || $code>=300 || !is_array($json) || !isset($json['access_token']) || !$json['access_token']) {
            throw new RuntimeException("OAuth token response unexpected: HTTP ".$code);
        }
        return $json;
    }

    function experimentmail__oauth_exchange_authorization_code($oauth_config,$redirect_uri,$authorization_code) {
        if (!is_array($oauth_config)) {
            throw new RuntimeException("OAuth identity config is not valid.");
        }
        $provider=isset($oauth_config['provider']) ? strtolower(trim((string)$oauth_config['provider'])) : "";
        $token_endpoint=isset($oauth_config['token_endpoint']) ? trim((string)$oauth_config['token_endpoint']) : "";
        $client_id=isset($oauth_config['client_id']) ? trim((string)$oauth_config['client_id']) : "";
        $client_secret=isset($oauth_config['client_secret']) ? trim((string)$oauth_config['client_secret']) : "";
        $scopes=isset($oauth_config['scopes']) ? trim((string)$oauth_config['scopes']) : "";

        if ($provider==="") {
            throw new RuntimeException("OAuth provider is missing.");
        }
        if ($token_endpoint==="") {
            throw new RuntimeException("OAuth token endpoint is missing.");
        }
        if ($client_id==="") {
            throw new RuntimeException("OAuth client id is missing.");
        }
        if (!is_string($authorization_code) || trim($authorization_code)==="") {
            throw new RuntimeException("OAuth authorization code is missing.");
        }
        if (!is_string($redirect_uri) || trim($redirect_uri)==="") {
            throw new RuntimeException("OAuth redirect URI is missing.");
        }

        $post=array(
            'grant_type'=>'authorization_code',
            'client_id'=>$client_id,
            'code'=>trim($authorization_code),
            'redirect_uri'=>trim($redirect_uri)
        );
        if ($client_secret!=="") {
            $post['client_secret']=$client_secret;
        }
        if ($provider==="microsoft" && $scopes!=="") {
            $post['scope']=$scopes;
        }

        $ch=curl_init($token_endpoint);
        curl_setopt_array($ch,array(
            CURLOPT_POST=>true,
            CURLOPT_POSTFIELDS=>http_build_query($post,'','&'),
            CURLOPT_RETURNTRANSFER=>true,
            CURLOPT_TIMEOUT=>20,
            CURLOPT_HTTPHEADER=>array(
                'Content-Type: application/x-www-form-urlencoded'
            )
        ));
        $raw=curl_exec($ch);
        if ($raw===false) {
            $err=curl_error($ch);
            curl_close($ch);
            throw new RuntimeException("OAuth code exchange failed: ".$err);
        }
        $code=curl_getinfo($ch,CURLINFO_HTTP_CODE);
        curl_close($ch);
        $json=json_decode($raw,true);
        if ($code<200 || $code>=300 || !is_array($json) || !isset($json['access_token']) || !$json['access_token']) {
            throw new RuntimeException("OAuth code exchange response unexpected: HTTP ".$code);
        }
        return $json;
    }

    function experimentmail__oauth_store_refresh_token($oauth_config,$refresh_token,$purpose='smtp_send') {
        if (!is_array($oauth_config)) {
            return false;
        }
        $identity=isset($oauth_config['identity']) ? strtolower(trim((string)$oauth_config['identity'])) : "";
        $provider=isset($oauth_config['provider']) ? strtolower(trim((string)$oauth_config['provider'])) : "";
        $refresh_token=trim((string)$refresh_token);
        if ($identity==="" || $provider==="" || $refresh_token==="") {
            return false;
        }
        $existing=experimentmail__oauth_tokens__load($purpose,$identity,$provider);
        $access_token="";
        $expires_at=0;
        if ($existing) {
            if (isset($existing['access_token'])) {
                $access_token=(string)$existing['access_token'];
            }
            if (isset($existing['access_token_expires_at'])) {
                $expires_at=(int)$existing['access_token_expires_at'];
            }
        }
        return experimentmail__oauth_tokens__save($purpose,$identity,$provider,$refresh_token,$access_token,$expires_at);
    }

    function experimentmail__oauth_store_token_response($oauth_config,$token_response,$purpose='smtp_send') {
        if (!is_array($oauth_config) || !is_array($token_response)) {
            return false;
        }
        $identity=isset($oauth_config['identity']) ? strtolower(trim((string)$oauth_config['identity'])) : "";
        $provider=isset($oauth_config['provider']) ? strtolower(trim((string)$oauth_config['provider'])) : "";
        if ($identity==="" || $provider==="") {
            return false;
        }
        $existing=experimentmail__oauth_tokens__load($purpose,$identity,$provider);
        $refresh_token="";
        if (isset($token_response['refresh_token']) && trim((string)$token_response['refresh_token'])!=="") {
            $refresh_token=trim((string)$token_response['refresh_token']);
        } elseif ($existing && isset($existing['refresh_token'])) {
            $refresh_token=(string)$existing['refresh_token'];
        }
        $access_token=isset($token_response['access_token']) ? trim((string)$token_response['access_token']) : "";
        if ($access_token==="" && $existing && isset($existing['access_token'])) {
            $access_token=(string)$existing['access_token'];
        }
        $expires_in=isset($token_response['expires_in']) ? (int)$token_response['expires_in'] : 0;
        $expires_at=0;
        if ($expires_in>0) {
            if ($expires_in<60) {
                $expires_in=60;
            }
            $expires_at=time()+$expires_in;
        } elseif ($existing && isset($existing['access_token_expires_at'])) {
            $expires_at=(int)$existing['access_token_expires_at'];
        }
        if ($refresh_token==="") {
            return false;
        }
        return experimentmail__oauth_tokens__save($purpose,$identity,$provider,$refresh_token,$access_token,$expires_at);
    }

    function experimentmail__oauth_get_valid_access_token($oauth_config,$purpose='smtp_send') {
        $purpose=trim((string)$purpose);
        if ($purpose==="") {
            $purpose='smtp_send';
        }
        if (!is_array($oauth_config)) {
            throw new RuntimeException("OAuth identity config is not valid.");
        }
        $identity=isset($oauth_config['identity']) ? strtolower(trim((string)$oauth_config['identity'])) : "";
        $provider=isset($oauth_config['provider']) ? strtolower(trim((string)$oauth_config['provider'])) : "";
        if ($identity==="") {
            throw new RuntimeException("OAuth identity is missing.");
        }
        if ($provider==="") {
            throw new RuntimeException("OAuth provider is missing.");
        }
        if (!isset($oauth_config['client_id']) || trim((string)$oauth_config['client_id'])==="") {
            throw new RuntimeException("OAuth client id is missing.");
        }
        $stored=experimentmail__oauth_tokens__load($purpose,$identity,$provider);
        $refresh_token=isset($oauth_config['refresh_token']) ? trim((string)$oauth_config['refresh_token']) : "";
        if ($refresh_token==="") {
            $refresh_token=($stored && isset($stored['refresh_token'])) ? trim((string)$stored['refresh_token']) : "";
        }
        if ($refresh_token==="") {
            throw new RuntimeException("OAuth refresh token is missing for ".$identity.".");
        }

        if ($stored && isset($stored['access_token']) && isset($stored['access_token_expires_at'])) {
            $stored_access=trim((string)$stored['access_token']);
            $stored_expires=(int)$stored['access_token_expires_at'];
            if ($stored_access!=="" && time() < ($stored_expires-60)) {
                return $stored_access;
            }
        }

        $json=experimentmail__oauth_refresh_access_token($oauth_config,$refresh_token);
        $access_token=(string)$json['access_token'];
        $expires_in=isset($json['expires_in']) ? (int)$json['expires_in'] : 3600;
        if ($expires_in<60) {
            $expires_in=60;
        }
        $expires_at=time()+$expires_in;
        $new_refresh=$refresh_token;
        if (isset($json['refresh_token']) && trim((string)$json['refresh_token'])!=="") {
            $new_refresh=trim((string)$json['refresh_token']);
        }
        $done=experimentmail__oauth_tokens__save($purpose,$identity,$provider,$new_refresh,$access_token,$expires_at);
        if (!$done) {
            experimentmail__phpmailer_debug_log("oauth token storage update failed for ".$identity);
        }
        return $access_token;
    }

    function experimentmail__oauth_build_oauth64($oauth_config,$purpose='smtp_send') {
        $identity=isset($oauth_config['identity']) ? trim((string)$oauth_config['identity']) : "";
        if ($identity==="") {
            throw new RuntimeException("OAuth identity is missing.");
        }
        $token=experimentmail__oauth_get_valid_access_token($oauth_config,$purpose);
        $auth_string="user=".$identity."\x01auth=Bearer ".$token."\x01\x01";
        return base64_encode($auth_string);
    }

    function experimentmail__split_addresses($addresses) {
        if (!is_string($addresses) || !$addresses) {
            return array();
        }
        $parts=preg_split("/[,;]+/",$addresses);
        $result=array();
        foreach ($parts as $part) {
            $addr=trim($part);
            if ($addr) {
                $result[]=$addr;
            }
        }
        return $result;
    }

    function experimentmail__parse_headers($headers) {
        $parsed=array();
        if (!is_string($headers) || !$headers) {
            return $parsed;
        }
        $lines=preg_split("/\r\n|\n|\r/",$headers);
        foreach ($lines as $line) {
            if (!$line || strpos($line,":")===false) {
                continue;
            }
            list($name,$value)=explode(":",$line,2);
            $name=trim($name);
            $value=trim($value);
            if ($name!=="" && $value!=="") {
                $parsed[]=array("name"=>$name,"value"=>$value);
            }
        }
        return $parsed;
    }

    function experimentmail__extract_email_address($value) {
        if (!is_string($value) || !$value) {
            return "";
        }
        $value=trim($value);
        if (preg_match("/<([^>]+)>/",$value,$matches)) {
            $value=trim($matches[1]);
        }
        if (filter_var($value,FILTER_VALIDATE_EMAIL)) {
            return $value;
        }
        return "";
    }

    function experimentmail__apply_header_addresses(&$mailer,$header_name,$header_value) {
        $addresses=experimentmail__split_addresses($header_value);
        foreach ($addresses as $address_line) {
            $address=experimentmail__extract_email_address($address_line);
            if (!$address) {
                continue;
            }
            if ($header_name=="cc") {
                $mailer->addCC($address);
            } elseif ($header_name=="bcc") {
                $mailer->addBCC($address);
            } elseif ($header_name=="reply-to") {
                $mailer->addReplyTo($address);
            }
        }
    }

    function experimentmail__send_via_phpmailer($recipient,$subject,$message,$headers,$env_sender="",$attachments=array()) {
        global $settings, $settings__phpmailer_host, $settings__phpmailer_port,
        $settings__phpmailer_smtp_secure, $settings__phpmailer_username,
        $settings__phpmailer_password, $settings__phpmailer_timeout;

        try {
            $mailer = new PHPMailer\PHPMailer\PHPMailer(true);
            $mailer->CharSet = "UTF-8";
            $mailer->isSMTP();
            $mailer->Host       = (isset($settings__phpmailer_host) && $settings__phpmailer_host) ? $settings__phpmailer_host : "127.0.0.1";
            $mailer->Port       = (isset($settings__phpmailer_port) && (int)$settings__phpmailer_port > 0) ? (int)$settings__phpmailer_port : 587;
            $mailer->SMTPAuth   = false;
            $mailer->Username   = (isset($settings__phpmailer_username)) ? (string)$settings__phpmailer_username : "";
            $mailer->Password   = (isset($settings__phpmailer_password)) ? (string)$settings__phpmailer_password : "";
            $mailer->SMTPSecure = (isset($settings__phpmailer_smtp_secure)) ? (string)$settings__phpmailer_smtp_secure : "";
            $mailer->Timeout    = (isset($settings__phpmailer_timeout) && (int)$settings__phpmailer_timeout > 0) ? (int)$settings__phpmailer_timeout : 15;

            if (experimentmail__phpmailer_debug_enabled()) {
                $mailer->SMTPDebug = 2;
                $mailer->Debugoutput = function ($str,$level) {
                    experimentmail__phpmailer_debug_log("SMTP[".$level."] ".$str);
                };
            }

            $parsed_headers=experimentmail__parse_headers($headers);

            $from_address=$env_sender;
            if (!$from_address && isset($settings["support_mail"])) {
                $from_address=$settings["support_mail"];
            }
            foreach ($parsed_headers as $header) {
                if (strtolower($header["name"])=="from") {
                    $header_from=experimentmail__extract_email_address($header["value"]);
                    if ($header_from) {
                        $from_address=$header_from;
                    }
                    break;
                }
            }
            if (!$from_address || !filter_var($from_address,FILTER_VALIDATE_EMAIL)) {
                return false;
            }
            $mailer->setFrom($from_address);
            $mailer->Sender=$from_address;

            $smtp_auth_type=experimentmail__phpmailer_smtp_auth_type();
            if ($smtp_auth_type=="none") {
                $mailer->SMTPAuth=false;
                $mailer->Username="";
                $mailer->Password="";
                $mailer->AuthType="";
            } elseif ($smtp_auth_type=="password") {
                $mailer->SMTPAuth=true;
                $mailer->AuthType="";
            } elseif ($smtp_auth_type=="oauth2") {
                $oauth_config=experimentmail__oauth_resolve_identity_config($from_address);
                if (!is_array($oauth_config)) {
                    experimentmail__phpmailer_debug_log("oauth2 config missing for from address ".$from_address);
                    return false;
                }
                if (!isset($oauth_config['identity']) || !filter_var($oauth_config['identity'],FILTER_VALIDATE_EMAIL)) {
                    experimentmail__phpmailer_debug_log("oauth2 identity invalid for from address ".$from_address);
                    return false;
                }
                $mailer->SMTPAuth=true;
                $mailer->AuthType='XOAUTH2';
                $mailer->Username=$oauth_config['identity'];
                $mailer->Password="";
                $mailer->setOAuth(new ORSEE_PHPMailer_OAuthTokenProvider($oauth_config,'smtp_send'));
            } else {
                experimentmail__phpmailer_debug_log("unknown smtp auth type ".$smtp_auth_type);
                return false;
            }

            $recipient_addresses=experimentmail__split_addresses($recipient);
            foreach ($recipient_addresses as $recipient_address_line) {
                $recipient_address=experimentmail__extract_email_address($recipient_address_line);
                if ($recipient_address) {
                    $mailer->addAddress($recipient_address);
                }
            }
            if (count($mailer->getToAddresses())<1) {
                return false;
            }

            if (isset($settings["bcc_all_outgoing_emails"]) && $settings["bcc_all_outgoing_emails"]=="y"
                && isset($settings["bcc_all_outgoing_emails__address"]) && $settings["bcc_all_outgoing_emails__address"]) {
                $bcc_address=experimentmail__extract_email_address($settings["bcc_all_outgoing_emails__address"]);
                if ($bcc_address) {
                    $mailer->addBCC($bcc_address);
                }
            }

            foreach ($parsed_headers as $header) {
                $header_name_lower=strtolower($header["name"]);
                if ($header_name_lower=="from" || $header_name_lower=="content-type" || $header_name_lower=="return-path") {
                    continue;
                }
                if ($header_name_lower=="cc" || $header_name_lower=="bcc" || $header_name_lower=="reply-to") {
                    experimentmail__apply_header_addresses($mailer,$header_name_lower,$header["value"]);
                } else {
                    $mailer->addCustomHeader($header["name"],$header["value"]);
                }
            }

            $mailer->Subject=$subject;
            $mailer->Body=$message;
            $mailer->isHTML(false);

            foreach ($attachments as $attachment) {
                if (isset($attachment["filename"]) && isset($attachment["content"])) {
                    $mailer->addStringAttachment($attachment["content"],$attachment["filename"]);
                }
            }

            if (!$mailer->send()) {
                experimentmail__phpmailer_debug_log("send failed: ".$mailer->ErrorInfo);
                return false;
            }
            experimentmail__phpmailer_debug_log("send ok");
            return true;
        } catch (Exception $e) {
            experimentmail__phpmailer_debug_log("exception: ".$e->getMessage());
            return false;
        }
    }

    function experimentmail__send($recipient,$subject,$message,$headers,$env_sender="") {
        global $settings;
        experimentmail__phpmailer_debug_log("experimentmail__send transport=".(experimentmail__use_phpmailer() ? "phpmailer" : "legacy"));
        if (experimentmail__use_phpmailer()) {
            return experimentmail__send_via_phpmailer($recipient,$subject,$message,$headers,$env_sender);
        }
        if (isset($settings['bcc_all_outgoing_emails']) && $settings['bcc_all_outgoing_emails']=='y'
            && isset($settings['bcc_all_outgoing_emails__address']) && $settings['bcc_all_outgoing_emails__address']) {
            $headers=$headers."Bcc: ".$settings['bcc_all_outgoing_emails__address']."\r\n";
        }
        if (!$env_sender) {
            $env_sender=$settings['support_mail'];
        }
        if ($settings['email_sendmail_type']=="indirect") {
            if ($settings['email_sendmail_path']) {
                $sendmail_path=$settings['email_sendmail_path'];
            } else {
                $sendmail_path="/usr/sbin/sendmail";
            }
            $sendmail = $sendmail_path." -t -i -f $env_sender";
            $fd = popen($sendmail, "w");
            fputs($fd, "To: $recipient\r\n");
            fputs($fd, $headers);
            fputs($fd, "Subject: $subject\r\n");
            fputs($fd, "X-Mailer: orsee\r\n\r\n");
            fputs($fd, $message);
            pclose($fd);
            $done=true;
        } else {
            $headers="Return-Path: ".$settings['support_mail']."\r\n".$headers;
            $done=mail($recipient,$subject,$message,$headers,'-f '.$env_sender);
        }
        return $done;
    }


    function experimentmail__mail_attach($to, $from, $subject, $message, $filename, $filecontent,$lb="\n") {
        if (experimentmail__use_phpmailer()) {
            $headers = "From: ".$from."\r\n";
            $message=html_entity_decode($message,ENT_COMPAT,'UTF-8');
            $subject=html_entity_decode($subject,ENT_COMPAT,'UTF-8');
            if (experimentmail__send_via_phpmailer($to,$subject,$message,$headers,"",array(array("filename"=>$filename,"content"=>$filecontent)))) {
                return true;
            }
            return false;
        }

        $mime_boundary = "<<<:" . md5(uniqid(mt_rand(), 1));
        $data = chunk_split(base64_encode($filecontent),60,"\r\n");
        $header = "From: ".$from.$lb;
        $header.= "MIME-Version: 1.0".$lb;
        $header.= "Content-Type: multipart/mixed;".$lb;
        $header.= " boundary=\"".$mime_boundary."\"".$lb;

        $content = "This is a multi-part message in MIME format.".$lb.$lb;
        $content.= "--".$mime_boundary.$lb;
        $content.= "Content-Type: text/plain; charset=\"UTF-8\"".$lb;
        $content.= "Content-Transfer-Encoding: 7bit".$lb.$lb;
        $message=html_entity_decode($message,ENT_COMPAT,'UTF-8');
        $content.= $message.$lb;
        $content.= "--".$mime_boundary.$lb;
        $content.= "Content-Disposition: attachment;".$lb;
        $content.= "Content-Type: Application/Octet-Stream; name=\"".$filename."\"".$lb;
        $content.= "Content-Transfer-Encoding: base64".$lb.$lb;
        $content.= $data.$lb;
        $content.= "--" . $mime_boundary . $lb;
        $subject=html_entity_decode($subject,ENT_COMPAT,'UTF-8');
        $subject='=?UTF-8?B?'.base64_encode($subject).'?=';
        if (experimentmail__send($to, $subject, $content, $header)) {
            return true;
        }
        return false;
    }

    function load_mail($mail_name,$lang) {
        global $authdata;
        $pars=array(':mail_name'=>$mail_name);
        $query="SELECT * FROM ".table('lang')."
            WHERE content_type='mail'
            AND content_name= :mail_name";
        $marr=orsee_query($query,$pars);
        if (isset($marr[$lang])) {
            $mailtext=$marr[$lang];
        } elseif (isset($authdata['language'])) {
            $mailtext=$marr[$authdata['language']];
        } elseif (isset($marr['en'])) {
            $mailtext=$marr['en'];
        } else {
            $mailtext='';
        }
        return $mailtext;
    }

    function experimentmail__load_invitation_text($experiment_id,$tlang="") {
        global $settings;
        if (!$tlang) {
            $tlang=$settings['public_standard_language'];
        }
        $pars=array(':experiment_id'=>$experiment_id);
        $query="SELECT * from ".table('lang')."
            WHERE content_type='experiment_invitation_mail'
            AND content_name= :experiment_id";
        $experiment_mail=orsee_query($query,$pars);
        return $experiment_mail[$tlang];
    }

    function experimentmail__load_bulk_mail($bulk_id,$tlang="") {
        global $settings;
        if (!$tlang) {
            $tlang=$settings['public_standard_language'];
        }
        $pars=array(':bulk_id'=>$bulk_id,':tlang'=>$tlang);
        $query="SELECT * from ".table('bulk_mail_texts')."
            WHERE bulk_id= :bulk_id
            AND lang= :tlang";
        $bulk_mail=orsee_query($query,$pars);
        if (!isset($bulk_mail['bulk_subject'])) {
            $tlang=$settings['public_standard_language'];
            $pars=array(':bulk_id'=>$bulk_id,':tlang'=>$tlang);
            $query="SELECT * from ".table('bulk_mail_texts')."
                WHERE bulk_id= :bulk_id
                AND lang= :tlang";
            $bulk_mail=orsee_query($query,$pars);
        }
        return $bulk_mail;
    }

    function experimentmail__gc_bulk_mail_texts() {
        $query="DELETE from ".table('bulk_mail_texts')." WHERE
            bulk_id NOT IN (SELECT DISTINCT bulk_id FROM or_mail_queue)";
        $done=or_query($query);
        return $done;
    }

    function process_mail_template($template,$vararray) {
        $output=explode("\n",$template);
        $vars=array_keys($vararray);
        foreach ($vars as $key) {
            $i=0;
            foreach ($output as $outputline) {
                $output[$i]=str_replace("#".$key."#",($vararray[$key] ?? ''),$output[$i]);
                $i++;
            }
        }
        $result="";
        foreach ($output as $outputline) {
            $result=$result.trim($outputline)."\n";
        }
        return $result;
    }

    // lists possible sessions for given experiment
    // only lists sessions with future registration end and which are not full
    function experimentmail__get_session_list($experiment_id,$tlang="") {
        global $settings, $lang;
        $savelang=$lang;
        if (!$tlang) {
            $tlang=$settings['public_standard_language'];
        }
        if (lang('lang')!=$tlang) {
            $lang=load_language($tlang);
        }
        $pars=array(':experiment_id'=>$experiment_id);
        $query="SELECT *
            FROM ".table('sessions')."
            WHERE experiment_id= :experiment_id
            AND session_status='live'
            ORDER BY session_start";
        $result=or_query($query,$pars);
        $list="";
        while ($s=pdo_fetch_assoc($result)) {
            $registration_unixtime=sessions__get_registration_end($s);
            $session_full=sessions__session_full('',$s);
            $now=time();
            if ($registration_unixtime > $now && !$session_full) {
                $is_rtl=lang__is_rtl(lang('lang'));
                $session_name=session__build_name($s,lang('lang'));
                $lab_name=laboratories__get_laboratory_name($s['laboratory_id']);
                $signup_part='';
                if (or_setting('include_sign_up_until_in_invitation')) {
                    if ($is_rtl) {
                        $signup_part=ortime__format($registration_unixtime,'',lang('lang')).' '.lang('registration_until');
                    } else {
                        $signup_part=lang('registration_until').' '.ortime__format($registration_unixtime,'',lang('lang'));
                    }
                }

                if ($is_rtl) {
                    if ($signup_part!=='') {
                        $list.=$signup_part.', '.$lab_name.', '.$session_name;
                    } else {
                        $list.=$lab_name.', '.$session_name;
                    }
                } else {
                    $list.=$session_name.' '.$lab_name;
                    if ($signup_part!=='') {
                        $list.=', '.$signup_part;
                    }
                }
                $list.="\n";
            }
        }
        $lang=$savelang;
        return $list;
    }

    function experimentmail__send_invitations_to_queue($experiment_id,$whom="not-invited") {
        switch ($whom) {
            case "not-invited":     $aquery=" AND invited=0 ";
                break;
            case "all":             $aquery="";
                break;
            default:                $aquery=" AND ".table('participants').".participant_id='0' ";
        }
        mt_srand((float)microtime()*1000000);
        $order="ORDER BY rand(".mt_rand().") ";
        $now=time();
        $status_query=participant_status__get_pquery_snippet("eligible_for_experiments");
        $pars=array(':experiment_id'=>$experiment_id);
        $query="INSERT INTO ".table('mail_queue')." (timestamp,mail_type,mail_recipient,experiment_id)
            SELECT ".$now.",'invitation', ".table('participants').".participant_id, experiment_id
            FROM ".table('participants').", ".table('participate_at')."
            WHERE experiment_id= :experiment_id
            AND ".table('participants').".participant_id=".table('participate_at').".participant_id ".
                $aquery."
            AND session_id = '0' AND pstatus_id = '0'";
        if ($status_query) {
            $query.=" AND ".$status_query;
        }
        $query.=" ".$order;
        $done=or_query($query,$pars);
        $count=pdo_num_rows($done);
        return $count;
    }

    function experimentmail__send_bulk_mail_to_queue($bulk_id,$part_array) {
        $return=false;
        if (is_array($part_array)) {
            $now=time();
            $done=shuffle($part_array);
            $pars=array();
            foreach ($part_array as $participant_id) {
                $pars[]=array(':participant_id'=>$participant_id,
                            ':bulk_id'=>$bulk_id);
            }
            $query="INSERT INTO ".table('mail_queue')."
                SET timestamp='".$now."',
                mail_type='bulk_mail',
                mail_recipient= :participant_id,
                bulk_id= :bulk_id";
            $return=or_query($query,$pars);
        }
        return $return;
    }


    function experimentmail__send_session_reminders_to_queue($session) {
        $pars=array(':experiment_id'=>$session['experiment_id'],
                    ':session_id'=>$session['session_id']);
        $query="INSERT INTO ".table('mail_queue')." (timestamp,mail_type,mail_recipient,experiment_id,session_id)
            SELECT UNIX_TIMESTAMP(),'session_reminder', participant_id, experiment_id, session_id
            FROM ".table('participate_at')."
            WHERE experiment_id= :experiment_id
            AND session_id= :session_id";
        $done=or_query($query,$pars);
        $count=pdo_num_rows($done);

        // update session table : reminder_sent
        $pars=array(':session_id'=>$session['session_id']);
        $query="UPDATE ".table('sessions')." SET reminder_sent='y' WHERE session_id= :session_id";
        $done=or_query($query,$pars);
        return $count;
    }


    function experimentmail__send_noshow_warnings_to_queue($session) {
        $noshow_clause=expregister__get_pstatus_query_snippet("noshow");
        $pars=array(':experiment_id'=>$session['experiment_id'],
                    ':session_id'=>$session['session_id']);
        $query="INSERT INTO ".table('mail_queue')." (timestamp,mail_type,mail_recipient,experiment_id,session_id)
            SELECT UNIX_TIMESTAMP(),'noshow_warning', participant_id, experiment_id, session_id
            FROM ".table('participate_at')."
            WHERE experiment_id= :experiment_id
            AND session_id= :session_id
            AND ".$noshow_clause;
        $done=or_query($query,$pars);
        $count=pdo_num_rows($done);
        return $count;
    }

    function experimentmail__set_reminder_checked($session_id) {
        // update session table : reminder_checked
        $pars=array(':session_id'=>$session_id);
        $query="UPDATE ".table('sessions')." SET reminder_checked='y' WHERE session_id = :session_id";
        $done=or_query($query,$pars);
        return $done;
    }

    function experimentmail__set_noshow_warnings_checked($session_id) {
        // update session table : noshow_warning_sent
        $pars=array(':session_id'=>$session_id);
        $query="UPDATE ".table('sessions')." SET noshow_warning_sent='y' WHERE session_id= :session_id";
        $done=or_query($query,$pars);
        return $done;
    }

    function experimentmail__mails_in_queue($type="",$experiment_id="",$session_id="") {
        $pars=array();
        if ($type) {
            $tquery=" AND mail_type= :type ";
            $pars[':type']=$type;
        } else {
            $tquery="";
        }
        if ($experiment_id) {
            $equery=" AND experiment_id= :experiment_id ";
            $pars[':experiment_id']=$experiment_id;
        } else {
            $equery="";
        }
        if ($session_id) {
            $squery=" AND session_id= :session_id ";
            $pars[':session_id']=$session_id;
        } else {
            $squery="";
        }
        $query="SELECT count(mail_id) as number FROM ".table('mail_queue')."
            WHERE mail_id>0 ".$tquery.$equery.$squery;
        $line=orsee_query($query,$pars);
        $number=$line['number'];
        return $number;
    }


    function experimentmail__send_mails_from_queue($number=0,$type="",$experiment_id="",$session_id="") {
        global $settings;
        $pars=array();
        if ($number>0) {
            $limit=" LIMIT :number ";
            $pars[':number']=$number;
        } else {
            $limit="";
        }
        if ($type) {
            $tquery=" AND mail_type= :type ";
            $pars[':type']=$type;
        } else {
            $tquery="";
        }
        if ($experiment_id) {
            $equery=" AND experiment_id= :experiment_id ";
            $pars[':experiment_id']=$experiment_id;
        } else {
            $equery="";
        }
        if ($session_id) {
            $squery=" AND session_id= :session_id ";
            $pars[':session_id']=$session_id;
        } else {
            $squery="";
        }

        $smails=array();
        $smails_ids=array();
        $invitations=array();
        $reminders=array();
        $bulks=array();
        $warnings=array();
        $errors=array();
        $reminder_text=array();
        $warning_text=array();
        $inv_texts=array();
        $exps=array();
        $sesss=array();
        $parts=array();
        $labs=array();
        $pform_fields=array();
        $slists=array();

        // first get mails to send
        $query="SELECT * FROM ".table('mail_queue')."
            WHERE error = '' ".
                $tquery.$equery.$squery."
            ORDER BY timestamp, mail_id ".
                $limit;
        $result=or_query($query,$pars);
        while ($line=pdo_fetch_assoc($result)) {
            $smails[]=$line;
            $smails_ids[]=$line['mail_id'];
        }

        // so we don't handle errors at all, and just delete here?!?
        //$pars=array();
        //foreach ($smails_ids as $id) $pars[]=array(':id'=>$id);
        //$query="DELETE FROM ".table('mail_queue')."
        //      WHERE mail_id = :id";
        //$done=or_query($query,$pars);

        foreach ($smails as $line) {
            $texp=$line['experiment_id'];
            $tsess=$line['session_id'];
            $tpart=$line['mail_recipient'];
            $ttype=$line['mail_type'];
            $tbulk=$line['bulk_id'];
            $continue=true;

            // well, if experiment_id, session_id, recipient, footer or inv_text, add to array
            if (!isset($exps[$texp]) && $texp) {
                $exps[$texp]=orsee_db_load_array("experiments",$texp,"experiment_id");
            }
            if (!isset($sesss[$tsess]) && $tsess) {
                $sesss[$tsess]=orsee_db_load_array("sessions",$tsess,"session_id");
            }
            if (!isset($parts[$tpart]) && $tpart) {
                $parts[$tpart]=orsee_db_load_array("participants",$tpart,"participant_id");
            }
            $tlang=$parts[$tpart]['language'];
            if (!isset($footers[$tlang])) {
                $footers[$tlang]=load_mail("public_mail_footer",$tlang);
            }
            if ($ttype=="session_reminder" && !isset($reminder_text[$texp][$tlang])) {
                $mailtext=false;
                if ($settings['enable_session_reminder_customization']=='y') {
                    $mailtext=experimentmail__get_customized_mailtext('experiment_session_reminder_mail',$texp,$tlang);
                }
                if (!isset($mailtext) || !$mailtext || !is_array($mailtext)) {
                    $mailtext['subject']=load_language_symbol('email_session_reminder_subject',$tlang);
                    $mailtext['body']=load_mail("public_session_reminder",$tlang);
                }
                $reminder_text[$texp][$tlang]=$mailtext;
            }
            if ($ttype=="noshow_warning" && !isset($warning_text[$tlang])) {
                $warning_text[$tlang]['text']=load_mail("public_noshow_warning",$tlang);
                $warning_text[$tlang]['subject']=load_language_symbol('email_noshow_warning_subject',$tlang);
            }
            if (($ttype=="session_reminder" || $ttype=="noshow_warning") && !isset($labs[$tsess][$tlang])) {
                $labs[$tsess][$tlang]=laboratories__get_laboratory_text($sesss[$tsess]['laboratory_id'],$tlang);
            }


            if ($ttype=="invitation" && !isset($inv_texts[$texp][$tlang])) {
                $inv_texts[$texp][$tlang]=experimentmail__load_invitation_text($texp,$tlang);
            }
            if ($ttype=="invitation" && !isset($slists[$texp][$tlang])) {
                $slists[$texp][$tlang]=experimentmail__get_session_list($texp,$tlang);
            }
            if ($ttype=="bulk_mail" && !isset($bulk_mails[$tbulk][$tlang])) {
                $bulk_mails[$tbulk][$tlang]=experimentmail__load_bulk_mail($tbulk,$tlang);
            }

            // check for missing values ...
            if (!isset($parts[$tpart]['participant_id'])) {
                $continue=false;
                // email error: no recipient
                $line['error'].="no_recipient:";
            } else {
                if (!isset($pform_fields[$tlang])) {
                    $pform_fields[$tlang]=participant__load_participant_email_fields($tlang);
                }
                $parts[$tpart]=experimentmail__fill_participant_details($parts[$tpart],$pform_fields[$tlang]);
            }

            if (!isset($exps[$texp]['experiment_id']) && ($ttype=="invitation" || $ttype=="session_reminder" || $ttype=="noshow_warning")) {
                $continue=false;
                // email error: no experiment id given
                $line['error'].="no_experiment:";
            }

            if (!isset($sesss[$tsess]['session_id']) && ($ttype=="session_reminder" || $ttype=="noshow_warning")) {
                $continue=false;
                // email error: no session id given
                $line['error'].="no_session:";
            }

            if (!isset($inv_texts[$texp][$tlang]) && $ttype=="invitation") {
                $continue=false;
                // email error: no inv_text given
                $line['error'].="no_inv_text:";
            }

            if (!isset($bulk_mails[$tbulk][$tlang]) && $ttype=="bulk_mail") {
                $continue=false;
                // email error: no bulk_mail given
                $line['error'].="no_bulk_mail_text:";
            }

            // fine, if no errors, add to arrays
            if ($continue) {
                switch ($line['mail_type']) {
                    case "invitation":
                        $invitations[]=$line;
                        break;
                    case "session_reminder":
                        $reminders[]=$line;
                        break;
                    case "noshow_warning":
                        $warnings[]=$line;
                        break;
                    case "bulk_mail":
                        $bulks[]=$line;
                        break;
                }
            } else {
                $errors[]=$line;
            }
        }

        // fine now we have everything we want, and we can proceed with sending the mails

        $mails_sent=0;
        $mails_errors=0;
        $invmails_not_sent=0;

        // reminders
        foreach ($reminders as $mail) {
            $tlang=$parts[$mail['mail_recipient']]['language'];
            $done=experimentmail__send_session_reminder_mail($mail,$parts[$mail['mail_recipient']],
                $exps[$mail['experiment_id']],$sesss[$mail['session_id']],
                $reminder_text[$mail['experiment_id']][$tlang],$labs[$mail['session_id']][$tlang],
                $footers[$tlang]);
            if ($done) {
                $mails_sent++;
                $deleted=experimentmail__delete_from_queue($mail['mail_id']);
            } else {
                $mail['error']="sending";
                $errors[]=$mail;
            }
        }

        // noshow warnings
        foreach ($warnings as $mail) {
            $tlang=$parts[$mail['mail_recipient']]['language'];
            $done=experimentmail__send_noshow_warning_mail($mail,$parts[$mail['mail_recipient']],
                $exps[$mail['experiment_id']],$sesss[$mail['session_id']],
                $warning_text[$tlang],$labs[$mail['session_id']][$tlang],
                $footers[$tlang]);
            if ($done) {
                $mails_sent++;
                $deleted=experimentmail__delete_from_queue($mail['mail_id']);
            } else {
                $mail['error']="sending";
                $errors[]=$mail;
            }
        }

        // invitations
        foreach ($invitations as $mail) {
            $tlang=$parts[$mail['mail_recipient']]['language'];
            if ($exps[$mail['experiment_id']]['experiment_type']=='laboratory' && (!trim($slists[$mail['experiment_id']][$tlang]))) {
                $done=true; // do not send invitation when session_list is empty!
                $invmails_not_sent++;
            } else {
                $done=experimentmail__send_invitation_mail($mail,$parts[$mail['mail_recipient']],
                    $exps[$mail['experiment_id']],$inv_texts[$mail['experiment_id']][$tlang],
                    $slists[$mail['experiment_id']][$tlang],$footers[$tlang]);
                if ($done) {
                    $mails_sent++;
                }
            }
            if ($done) {
                $deleted=experimentmail__delete_from_queue($mail['mail_id']);
            } else {
                $mail['error']="sending";
                $errors[]=$mail;
            }
        }

        // bulks
        foreach ($bulks as $mail) {
            $tlang=$parts[$mail['mail_recipient']]['language'];
            $done=experimentmail__send_bulk_mail($mail,$parts[$mail['mail_recipient']],$bulk_mails[$mail['bulk_id']][$tlang],$footers[$tlang]);
            if ($done) {
                $mails_sent++;
                $deleted=experimentmail__delete_from_queue($mail['mail_id']);
            } else {
                $mail['error']="sending";
                $errors[]=$mail;
            }
        }
        $done=experimentmail__gc_bulk_mail_texts();

        // handle errors
        $pars=array();
        $mails_errors=count($errors);
        if ($mails_errors>0) {
            foreach ($errors as $mail) {
                $pars[]=array(':error'=>$mail['error'],':mail_id'=>$mail['mail_id']);
            }
            $query="UPDATE ".table('mail_queue')."
                SET error= :error
                WHERE mail_id= :mail_id";
            $done=or_query($query,$pars);
        }
        $mess['mails_sent']=$mails_sent;
        $mess['mails_invmails_not_sent']=$invmails_not_sent;
        $mess['mails_errors']=$mails_errors;
        return $mess;
    }

    function experimentmail__delete_from_queue($mail_id) {
        $pars=array(':mail_id'=>$mail_id);
        $query="DELETE FROM ".table('mail_queue')."
            WHERE mail_id= :mail_id";
        $result=or_query($query,$pars);
        return $result;
    }

    function experimentmail__fill_participant_details($participant_array,$pform_fields,$tlang='') {
        // $participant_array is a row from or_participants
        // $pform_fields is loaded with participant__load_participant_email_fields(lang)
        // return value is the participant array with ids replaced by values from or_lang
        if (!$tlang) {
            $tlang=lang('lang');
        }
        foreach ($pform_fields as $f) {
            if (!isset($f['mysql_column_name']) || !isset($participant_array[$f['mysql_column_name']])) {
                continue;
            }
            if (preg_match("/(radioline|select_list|select_lang|radioline_lang)/",$f['type']) && isset($f['lang'][$participant_array[$f['mysql_column_name']]])) {
                $participant_array[$f['mysql_column_name']]=$f['lang'][$participant_array[$f['mysql_column_name']]];
            } elseif ($f['type']==='checkboxlist_lang') {
                $raw_value=(string)$participant_array[$f['mysql_column_name']];
                $raw_values=explode(',',$raw_value);
                $display_values=array();
                foreach ($raw_values as $raw_item) {
                    $raw_item=trim($raw_item);
                    if ($raw_item==='') {
                        continue;
                    }
                    if (isset($f['lang'][$raw_item])) {
                        $display_values[]=$f['lang'][$raw_item];
                    } else {
                        $display_values[]=$raw_item;
                    }
                }
                $participant_array[$f['mysql_column_name']]=implode(', ',$display_values);
            } elseif ($f['type']==='date') {
                $raw_value=(string)$participant_array[$f['mysql_column_name']];
                $date_mode=(isset($f['date_mode']) ? $f['date_mode'] : 'ymd');
                $date_value=ortime__format_ymd_localized($raw_value,'',$date_mode);
                if ($date_value) {
                    $participant_array[$f['mysql_column_name']]=$date_value;
                }
            } elseif ($f['type']==='boolean') {
                $bool_value=(string)$participant_array[$f['mysql_column_name']];
                if ($bool_value==='y') {
                    $participant_array[$f['mysql_column_name']]=load_language_symbol('y',$tlang);
                } elseif ($bool_value==='n') {
                    $participant_array[$f['mysql_column_name']]=load_language_symbol('n',$tlang);
                }
            }
        }
        return $participant_array;
    }

    function experimentmail__preview_fake_participant_details($pform_fields) {
        // $pform_fields is loaded with participant__load_participant_email_fields(lang)
        // return value is a fake participant array with ids replaced by names of columns
        $participant_array=array('participant_id'=>0,'participant_id_crypt'=>'UVWXYZ');
        foreach ($pform_fields as $f) {
            $participant_array[$f['mysql_column_name']]=$f['column_name'];
        }
        return $participant_array;
    }

    function experimentmail__preview_fake_session_details($experiment_id) {
        $pars=array(':experiment_id'=>$experiment_id);
        $query="SELECT * FROM ".table('sessions')."
            WHERE experiment_id = :experiment_id
            ORDER BY if(session_status='live',0,1), session_start DESC
            LIMIT 1";
        $session=orsee_query($query,$pars);
        if (!isset($session['session_id'])) {
            $session=array();
            $session['session_start']=ortime__unixtime_to_sesstime();
            $session['session_duration_hour']=1;
            $session['session_duration_minute']=30;
            $labs=laboratories__get_laboratories();
            $randlab=array_rand($labs);
            $session['laboratory_id']=$randlab;
        }
        return $session;
    }

    function experimentmail__get_session_reminder_details($part,$exp,$session,$lab) {
        $part['edit_link']=experimentmail__build_edit_link($part);
        $part['enrolment_link']=experimentmail__build_lab_registration_link($part);
        $part['experiment_name']=$exp['experiment_public_name'];
        $part['session_date']=session__build_name($session,$part['language']);
        $part['lab_name']=laboratories__strip_lab_name($lab);
        $part['lab_address']=laboratories__strip_lab_address($lab);
        return $part;
    }

    function experimentmail__get_secondary_email_addresses($part,$fields=array()) {
        if (!is_array($fields) || count($fields)<1) {
            $fields=participantform__load();
        }
        $secondary=array();
        foreach ($fields as $field) {
            if (!isset($field['type']) || !in_array($field['type'],array('textline','email'),true)) {
                continue;
            }
            if (!isset($field['use_as_secondary_email']) || $field['use_as_secondary_email']!=='y') {
                continue;
            }
            if (!isset($field['mysql_column_name']) || !isset($part[$field['mysql_column_name']])) {
                continue;
            }
            $address=trim((string)$part[$field['mysql_column_name']]);
            if ($address!=='') {
                $secondary[]=$address;
            }
        }
        return $secondary;
    }

    function experimentmail__recipient_list_for_queue_mail($part,$mail_type) {
        $valid_addresses=array();
        $error_addresses=array();

        $primary_address=(isset($part['email']) ? trim((string)$part['email']) : '');
        if (filter_var($primary_address,FILTER_VALIDATE_EMAIL)) {
            $valid_addresses[]=$primary_address;
        } else {
            $error_addresses[]=$primary_address;
        }

        $secondary_whitelist=array(
            'invitation',
            'session_reminder',
            'noshow_warning',
            'bulk_mail',
            'enrolment_confirmation',
            'enrolment_cancellation'
        );
        if (in_array($mail_type,$secondary_whitelist,true)) {
            $secondary_addresses=experimentmail__get_secondary_email_addresses($part);
            foreach ($secondary_addresses as $secondary_address) {
                if (filter_var($secondary_address,FILTER_VALIDATE_EMAIL)) {
                    $valid_addresses[]=$secondary_address;
                } else {
                    $error_addresses[]=$secondary_address;
                }
            }
        }

        return array(
            'valid_addresses'=>$valid_addresses,
            'error_addresses'=>$error_addresses
        );
    }

    function experimentmail__send_to_recipient_list($recipients,$subject,$message,$headers) {
        if (!is_array($recipients) || count($recipients)<1) {
            return false;
        }
        $done=true;
        foreach ($recipients as $recipient) {
            $sent=experimentmail__mail($recipient,$subject,$message,$headers);
            if (!$sent) {
                $done=false;
            }
        }
        return $done;
    }

    function experimentmail__log_recipient_issue($part,$mail,$error_addresses,$details=array()) {
        if (!is_array($error_addresses) || count($error_addresses)<1) {
            return;
        }
        $participant_id=(isset($part['participant_id']) ? $part['participant_id'] : '');
        $mail_type=(isset($mail['mail_type']) ? $mail['mail_type'] : '');
        $target='participant_id:'.$participant_id.
                ', errors in: '.implode(', ',$error_addresses).
                ', mail_type:'.$mail_type;
        if (isset($details['experiment_id']) && $details['experiment_id']) {
            $target.=', experiment_id:'.$details['experiment_id'];
        }
        log__cron_job('mail_queue_email_recipient_issue',$target,'','process_mail_queue');
    }

    function experimentmail__send_session_reminder_mail($mail,$part,$exp,$session,$reminder_text,$lab,$footer) {
        global $settings;
        $part=experimentmail__get_session_reminder_details($part,$exp,$session,$lab);
        $mailtext=stripslashes($reminder_text['body']);
        $subject=$reminder_text['subject'];
        $message=process_mail_template($mailtext,$part)."\n".process_mail_template($footer,$part);
        $sender=experimentmail__get_sender_email($exp);
        $headers="From: ".$sender."\r\n";
        $recipients=experimentmail__recipient_list_for_queue_mail($part,'session_reminder');
        $valid_email_addresses=$recipients['valid_addresses'];
        $error_addresses=$recipients['error_addresses'];
        experimentmail__log_recipient_issue($part,$mail,$error_addresses,array('experiment_id'=>$exp['experiment_id']));
        if (count($valid_email_addresses)<1) {
            return false;
        }
        $done=experimentmail__send_to_recipient_list($valid_email_addresses,$subject,$message,$headers);
        return $done;
    }

    function experimentmail__get_noshow_warning_details($part,$exp,$session,$lab) {
        global $settings;
        $part['edit_link']=experimentmail__build_edit_link($part);
        $part['enrolment_link']=experimentmail__build_lab_registration_link($part);
        $part['experiment_name']=$exp['experiment_public_name'];
        $part['session_date']=session__build_name($session,$part['language']);
        $part['lab_name']=laboratories__strip_lab_name($lab);
        $part['lab_address']=laboratories__strip_lab_address($lab);
        $part['max_noshows']=$settings['automatic_exclusion_noshows'];
        return $part;
    }


    function experimentmail__send_noshow_warning_mail($mail,$part,$exp,$session,$warning_text,$lab,$footer) {
        global $settings;
        $part=experimentmail__get_noshow_warning_details($part,$exp,$session,$lab);
        $mailtext=stripslashes($warning_text['text']);
        $subject=$warning_text['subject'];
        $message=process_mail_template($mailtext,$part)."\n".process_mail_template($footer,$part);
        $sender=$settings['support_mail'];
        $headers="From: ".$sender."\r\n";
        $recipients=experimentmail__recipient_list_for_queue_mail($part,'noshow_warning');
        $valid_email_addresses=$recipients['valid_addresses'];
        $error_addresses=$recipients['error_addresses'];
        experimentmail__log_recipient_issue($part,$mail,$error_addresses,array('experiment_id'=>$exp['experiment_id']));
        if (count($valid_email_addresses)<1) {
            return false;
        }
        $done=experimentmail__send_to_recipient_list($valid_email_addresses,$subject,$message,$headers);
        return $done;
    }

    function experimentmail__send_participant_exclusion_mail($part) {
        global $settings;
        $mailtext=stripslashes(load_mail("public_participant_exclusion",$part['language']));
        $subject=load_language_symbol('participant_exclusion_mail_subject',$part['language']);
        $recipient=$part['email'];
        $message=process_mail_template($mailtext,$part);
        $sender=$settings['support_mail'];
        $headers="From: ".$sender."\r\n";
        $done=experimentmail__mail($recipient,$subject,$message,$headers);
        return $done;
    }

    function experimentmail__send_reminder_notice($line,$number,$sent,$disclaimer="") {
        global $settings;
        $experimenters=db_string_to_id_array($line['experimenter_mail']);
        foreach ($experimenters as $experimenter) {
            $mail=orsee_db_load_array("admin",$experimenter,"admin_id");
            $tlang= ($mail['language']) ? $mail['language'] : $settings['admin_standard_language'];
            $lang=load_language($tlang);
            $mail['session_name']=session__build_name($line,$tlang);
            $mail['experiment_name']=$line['experiment_name'];
            $mail['nr_participants'] = ($sent) ? $number : 0;
            switch ($disclaimer) {
                case 'part_needed':
                    $mail['disclaimer']=load_language_symbol('reminder_not_sent_part_needed',$tlang);
                    break;
                case 'part_reserve':
                    $mail['disclaimer']=load_language_symbol('reminder_not_sent_part_reserve',$tlang);
                    break;
                default:
                    $mail['disclaimer']="";
            }

            if ($mail['disclaimer']) {
                $sub_notice=load_language_symbol('subject_for_session_reminder_error_notice',$tlang);
            } else {
                $sub_notice=load_language_symbol('subject_for_session_reminder_ok_notice',$tlang);
            }
            $recipient=$mail['email'];
            $subject=$sub_notice.' '.$mail['experiment_name'].' '.$mail['session_name'];
            $mailtext=load_mail("admin_session_reminder_notice",$tlang);
            $message=process_mail_template($mailtext,$mail);
            $headers="From: ".$settings['support_mail']."\r\n";
            $done=experimentmail__mail($recipient,$subject,$message,$headers);
        }
        return $done;
    }

    function experimentmail__get_invitation_mail_details($part,$exp,$slist) {
        global $settings;
        $part['edit_link']=experimentmail__build_edit_link($part);
        $part['enrolment_link']=experimentmail__build_lab_registration_link($part);
        $part['experiment_name']=$exp['experiment_public_name'];
        $part['sessionlist']=$slist;
        $part['link']=experimentmail__build_lab_registration_link($part);
        $part['public_experiment_note']=$exp['public_experiment_note'];
        $part['ethics_by']=$exp['ethics_by'];
        $part['ethics_number']=$exp['ethics_number'];
        return $part;
    }

    function experimentmail__send_invitation_mail($mail,$part,$exp,$inv_text,$slist,$footer) {
        global $settings;
        $part=experimentmail__get_invitation_mail_details($part,$exp,$slist);
        // split in subject and text
        $subject=stripslashes(str_replace(strstr($inv_text,"\n"),"",$inv_text));
        $mailtext=stripslashes(substr($inv_text,strpos($inv_text,"\n")+1,strlen($inv_text)));
        $message=process_mail_template($mailtext,$part)."\n".process_mail_template($footer,$part);
        $sender=experimentmail__get_sender_email($exp);
        $headers="From: ".$sender."\r\n";
        $recipients=experimentmail__recipient_list_for_queue_mail($part,'invitation');
        $valid_email_addresses=$recipients['valid_addresses'];
        $error_addresses=$recipients['error_addresses'];
        experimentmail__log_recipient_issue($part,$mail,$error_addresses,array('experiment_id'=>$exp['experiment_id']));
        if (count($valid_email_addresses)<1) {
            return false;
        }
        $done=experimentmail__send_to_recipient_list($valid_email_addresses,$subject,$message,$headers);
        $done2=experimentmail__update_invited_flag($mail);
        return $done;
    }

    function experimentmail__get_bulk_mail_details($part) {
        $part['edit_link']=experimentmail__build_edit_link($part);
        $part['enrolment_link']=experimentmail__build_lab_registration_link($part);
        return $part;
    }

    function experimentmail__send_bulk_mail($mail,$part,$bulk_mail,$footer) {
        global $settings;
        $part=experimentmail__get_bulk_mail_details($part);
        // split in subject and text
        $subject=stripslashes($bulk_mail['bulk_subject']);
        $mailtext=stripslashes($bulk_mail['bulk_text']);
        $message=process_mail_template($mailtext,$part)."\n".process_mail_template($footer,$part);
        $sender=$settings['support_mail'];
        $headers="From: ".$sender."\r\n";
        $recipients=experimentmail__recipient_list_for_queue_mail($part,'bulk_mail');
        $valid_email_addresses=$recipients['valid_addresses'];
        $error_addresses=$recipients['error_addresses'];
        experimentmail__log_recipient_issue($part,$mail,$error_addresses,array());
        if (count($valid_email_addresses)<1) {
            return false;
        }
        $done=experimentmail__send_to_recipient_list($valid_email_addresses,$subject,$message,$headers);
        return $done;
    }

    function experimentmail__update_invited_flag($mail) {
        $pars=array(':participant_id'=>$mail['mail_recipient'],
                    ':experiment_id'=>$mail['experiment_id']);
        $query="UPDATE ".table('participate_at')."
            SET invited=1
            WHERE participant_id= :participant_id
            AND experiment_id= :experiment_id";
        $result=or_query($query,$pars);
        return $result;
    }

    function experimentmail__build_edit_link($participant) {
        global $settings__root_url, $settings;
        if (isset($settings['subject_authentication']) && $settings['subject_authentication']=='username_password') {
            $token_string='';
        } else {
            $token_string="?p=".urlencode($participant['participant_id_crypt']);
        }
        $edit_link=$settings__root_url."/public/participant_edit.php".$token_string;
        return $edit_link;
    }

    function experimentmail__build_lab_registration_link($participant) {
        global $settings__root_url, $settings;
        if (isset($settings['subject_authentication']) && $settings['subject_authentication']=='username_password') {
            $token_string='';
        } else {
            $token_string="?p=".urlencode($participant['participant_id_crypt']);
        }
        $reg_link=$settings__root_url."/public/participant_show.php".$token_string;
        return $reg_link;
    }

    function experimentmail__mail_edit_link($participant_id) {
        global $lang, $authdata, $settings;
        $participant=orsee_db_load_array("participants",$participant_id,"participant_id");
        $participant['edit_link']=experimentmail__build_edit_link($participant);
        $participant['enrolment_link']=experimentmail__build_lab_registration_link($participant);
        if (isset($_SESSION['authdata']['language']) && $_SESSION['authdata']['language']) {
            $maillang=$_SESSION['authdata']['language'];
        } else {
            $maillang=$participant['language'];
        }
        $mailtext=load_mail("public_mail_footer",$maillang);
        $message=process_mail_template($mailtext,$participant);
        $headers="From: ".$settings['support_mail']."\r\n";
        experimentmail__mail($participant['email'],lang('subject_for_edit_link_mail'),$message,$headers);
    }

    function experimentmail__mail_pwreset_link($participant) {
        global $lang, $settings;
        $message=experimentmail__get_pwreset_mail_text($participant);
        $headers="From: ".$settings['support_mail']."\r\n";
        experimentmail__mail($participant['email'],lang('password_reset_email_subject'),$message,$headers);
    }

    function experimentmail__get_pwreset_mail_text($participant) {
        global $authdata, $lang, $settings__root_url, $settings;
        $maillang=experimentmail__get_language($participant['language']);
        $pform_fields=participant__load_participant_email_fields($maillang);
        $participant=experimentmail__fill_participant_details($participant,$pform_fields,$maillang);
        $exptype_ids=db_string_to_id_array($participant['subscriptions']);
        $exptypes=load_external_experiment_types();
        $invnames=array();
        foreach ($exptype_ids as $exptype_id) {
            if (isset($exptypes[$exptype_id][$maillang])) {
                $invnames[]=$exptypes[$exptype_id][$maillang];
            } elseif (isset($exptypes[$exptype_id][lang('lang')])) {
                $invnames[]=$exptypes[$exptype_id][lang('lang')];
            }
        }
        $participant['invitations']=implode(", ",$invnames);
        $participant['password_reset_link']=$settings__root_url."/public/participant_reset_pw.php?t=".urlencode($participant['pwreset_token']);
        $mailtext=load_mail("public_password_reset",$maillang);
        $message=process_mail_template($mailtext,$participant);
        return $message;
    }


    function experimentmail__get_language($partlang) {
        global $authdata;
        if (isset($authdata['language']) && $authdata['language']) {
            $maillang=$authdata['language'];
        } else {
            $maillang=$partlang;
        }
        return $maillang;
    }

    function experimentmail__get_admin_language($adminlang) {
        global $authdata, $settings;
        if (isset($authdata['language']) && $authdata['language']) {
            $maillang=$authdata['language'];
        } elseif ($adminlang) {
            $maillang=$adminlang;
        } else {
            $maillang=$settings['admin_standard_language'];
        }
        return $maillang;
    }

    function experimentmail__get_mail_footer($participant) {
        global $lang, $settings;
        $participant['edit_link']=experimentmail__build_edit_link($participant);
        $participant['enrolment_link']=experimentmail__build_lab_registration_link($participant);
        $maillang=experimentmail__get_language($participant['language']);
        $mailtext=load_mail("public_mail_footer",$maillang);
        $footer=process_mail_template($mailtext,$participant);
        return $footer;
    }

    function experimentmail__get_admin_footer($maillang,$admin) {
        if (!$maillang) {
            $maillang=experimentmail__get_admin_language($admin['language']);
        }
        $mailtext=load_mail("admin_mail_footer",$maillang);
        $footer=process_mail_template($mailtext,$admin);
        return $footer;
    }

    function experimentmail__confirmation_mail($participant) {
        global $authdata, $lang, $settings__root_url, $settings;
        $message=experimentmail__get_confirmation_mail_text($participant);
        $headers="From: ".$settings['support_mail']."\r\n";
        experimentmail__mail($participant['email'],lang('registration_email_subject'),$message,$headers);
    }

    function experimentmail__get_confirmation_mail_text($participant) {
        global $authdata, $lang, $settings__root_url, $settings;
        $maillang=experimentmail__get_language($participant['language']);
        $pform_fields=participant__load_participant_email_fields($maillang);
        $participant=experimentmail__fill_participant_details($participant,$pform_fields,$maillang);
        $exptype_ids=db_string_to_id_array($participant['subscriptions']);
        $exptypes=load_external_experiment_types();
        $invnames=array();
        foreach ($exptype_ids as $exptype_id) {
            if (isset($exptypes[$exptype_id][$maillang])) {
                $invnames[]=$exptypes[$exptype_id][$maillang];
            } elseif (isset($exptypes[$exptype_id][lang('lang')])) {
                $invnames[]=$exptypes[$exptype_id][lang('lang')];
            }
        }
        $participant['invitations']=implode(", ",$invnames);
        $participant['registration_link']=$settings__root_url."/public/participant_confirm.php?c=".urlencode($participant['confirmation_token']);
        $mailtext=load_mail("public_system_registration",$maillang);
        $message=process_mail_template($mailtext,$participant);
        return $message;
    }


    function experimentmail__get_experiment_registration_details($part,$exp,$sess,$lab) {
        $part['lab_name']=laboratories__strip_lab_name($lab);
        $part['lab_address']=laboratories__strip_lab_address($lab);
        $part['session']=session__build_name($sess,$part['language']);
        $part['experiment']=$exp['experiment_public_name'];
        $part['duration']=$sess['session_duration_hour'].":".$sess['session_duration_minute'];
        return $part;
    }

    function experimentmail__get_customized_mailtext($type,$experiment_id,$maillang="") {
        if (!$maillang) {
            $maillang=lang('lang');
        } $mailtext=array();
        $fulltext=language__get_item($type,$experiment_id,$maillang);
        if ($fulltext) {
            $mailtext['subject']=str_replace(strstr($fulltext,"\n"),"",$fulltext);
            $mailtext['body']=substr($fulltext,strpos($fulltext,"\n")+1,strlen($fulltext));
            return $mailtext;
        } else {
            return false;
        }
    }


    function experimentmail__get_sender_email($experiment) {
        global $settings;
        if ($settings['enable_editing_of_experiment_sender_email']=='y' && $experiment['sender_mail']) {
            return $experiment['sender_mail'];
        } else {
            return $settings['support_mail'];
        }
    }

    function experimentmail__experiment_registration_mail($participant,$session) {
        global $lang, $settings;
        // load experiment
        $experiment=orsee_db_load_array("experiments",$session['experiment_id'],"experiment_id");

        $maillang=experimentmail__get_language($participant['language']);

        // load laboratory
        $lab=laboratories__get_laboratory_text($session['laboratory_id'],$maillang);

        $pform_fields=participant__load_participant_email_fields($maillang);
        $experimentmail=experimentmail__fill_participant_details($participant,$pform_fields);
        $experimentmail=experimentmail__get_experiment_registration_details($experimentmail,$experiment,$session,$lab);

        $mailtext=false;
        if ($settings['enable_enrolment_confirmation_customization']=='y') {
            $mailtext=experimentmail__get_customized_mailtext('experiment_enrolment_conf_mail',$session['experiment_id'],$maillang);
        }
        if (!isset($mailtext) || !$mailtext || !is_array($mailtext)) {
            $mailtext['subject']=load_language_symbol('enrolment_email_subject',$maillang);
            $mailtext['body']=load_mail("public_experiment_registration",$maillang);
        }

        $message=process_mail_template($mailtext['body'],$experimentmail);
        $message=$message."\n".experimentmail__get_mail_footer($participant);
        $sendermail=experimentmail__get_sender_email($experiment);
        $headers="From: ".$sendermail."\r\n";
        $recipients=experimentmail__recipient_list_for_queue_mail($participant,'enrolment_confirmation');
        $valid_email_addresses=$recipients['valid_addresses'];
        $error_addresses=$recipients['error_addresses'];
        if (count($error_addresses)>0) {
            log__participant(
                'participant_email_recipient_issue',
                $participant['participant_id'],
                'errors in: '.implode(', ',$error_addresses).
                ', mail_type:enrolment_confirmation'.
                ', experiment_id:'.$session['experiment_id'].
                ', session_id:'.$session['session_id']
            );
        }
        if (count($valid_email_addresses)<1) {
            return false;
        }
        return experimentmail__send_to_recipient_list($valid_email_addresses,$mailtext['subject'],$message,$headers);
    }

    function experimentmail__experiment_cancellation_mail($participant,$session) {
        global $lang, $settings;
        // load experiment
        $experiment=orsee_db_load_array("experiments",$session['experiment_id'],"experiment_id");

        $maillang=experimentmail__get_language($participant['language']);

        // load laboratory
        $lab=laboratories__get_laboratory_text($session['laboratory_id'],$maillang);

        $pform_fields=participant__load_participant_email_fields($maillang);
        $experimentmail=experimentmail__fill_participant_details($participant,$pform_fields);
        $experimentmail=experimentmail__get_experiment_registration_details($experimentmail,$experiment,$session,$lab);

        $mailtext['subject']=load_language_symbol('enrolment_cancellation_email_subject',$maillang);
        $mailtext['body']=load_mail("public_experiment_enrolment_cancellation",$maillang);

        $message=process_mail_template($mailtext['body'],$experimentmail);
        $message=$message."\n".experimentmail__get_mail_footer($participant);
        $sendermail=experimentmail__get_sender_email($experiment);
        $headers="From: ".$sendermail."\r\n";
        $recipients=experimentmail__recipient_list_for_queue_mail($participant,'enrolment_cancellation');
        $valid_email_addresses=$recipients['valid_addresses'];
        $error_addresses=$recipients['error_addresses'];
        if (count($error_addresses)>0) {
            log__participant(
                'participant_email_recipient_issue',
                $participant['participant_id'],
                'errors in: '.implode(', ',$error_addresses).
                ', mail_type:enrolment_cancellation'.
                ', experiment_id:'.$session['experiment_id'].
                ', session_id:'.$session['session_id']
            );
        }
        if (count($valid_email_addresses)<1) {
            return false;
        }
        return experimentmail__send_to_recipient_list($valid_email_addresses,$mailtext['subject'],$message,$headers);
    }



    function experimentmail__send_registration_notice($line) {
        global $settings, $expadmindata;
        $reg=experiment__count_participate_at($line['experiment_id'],$line['session_id']);
        $experimenters=db_string_to_id_array($line['experimenter_mail']);

        foreach ($experimenters as $experimenter) {
            $admin=orsee_db_load_array("admin",$experimenter,"admin_id");
            if (isset($admin['admin_id'])) {
                $tlang= ($admin['language']) ? $admin['language'] : $settings['admin_standard_language'];
                $lang=load_language($tlang);
                $admin['session_name']=session__build_name($line,$tlang);
                $admin['experiment_name']=$line['experiment_name'];
                $admin['registered'] = $reg;
                $admin['status']=session__get_status($line,$tlang,$reg);
                $admin['needed']=$line['part_needed'];
                $admin['reserve']=$line['part_reserve'];
                $subject=load_language_symbol('subject_for_registration_notice',$tlang);
                $subject.=' '.$admin['experiment_name'].', '.$admin['session_name'];
                $recipient=$admin['email'];
                $mailtext=load_mail("admin_registration_notice",$tlang)."\n".
                            experimentmail__get_admin_footer($tlang,$admin)."\n";
                $message=process_mail_template($mailtext,$admin);
                $now=time();
                $list_name=lang('participant_list_filename').' '.date("Y-m-d",$now);
                $list_filename=str_replace(" ","_",$list_name).".pdf";
                $old_expadmindata=(isset($expadmindata) ? $expadmindata : array());
                $expadmindata=$admin;
                $expadmindata['rights']=admin__load_admin_rights($expadmindata['admin_type']);
                $list_file=pdfoutput__make_part_list($line['experiment_id'],$line['session_id'],'registered','lname,fname',true,$tlang);
                $expadmindata=$old_expadmindata;
                $done=experimentmail__mail_attach($recipient,$settings['support_mail'],$subject,$message,$list_filename,$list_file);
            }
        }

        // update session table : reg_notice_sent
        $pars=array(':session_id'=>$line['session_id']);
        $query="UPDATE ".table('sessions')." SET reg_notice_sent='y' WHERE session_id= :session_id ";
        $done2=or_query($query,$pars);
        return $done;
    }


    function experimentmail__send_calendar() {
        global $lang, $settings;
        $now=time();
        if (isset($settings['emailed_calendar_included_months']) && $settings['emailed_calendar_included_months']>0) {
            $number_of_months=$settings['emailed_calendar_included_months']-1;
        } else {
            $number_of_months=1;
        }
        $from=$settings['support_mail'];

        // remember the current language for later reset
        $old_lang=lang('lang');

        // preload details with current language
        $maillang=$old_lang;
        $mail_subject=lang('experiment_calendar').' '.ortime__format($now,'hide_time:true');
        $cal_name=lang('experiment_calendar').' '.date("Y-m-d",$now);
        $cal_filename=str_replace(" ","_",$cal_name).".pdf";
        $cal_file=pdfoutput__make_pdf_calendar($now,false,true,$number_of_months,true);

        // get experimenters who want to receive the calendar
        $query="SELECT *
            FROM ".table('admin')."
            WHERE get_calendar_mail='y'
            AND disabled='n'
            ORDER BY language";
        $result=or_query($query);
        $i=0;
        $rec_count=pdo_num_rows($result);
        while ($admin = pdo_fetch_assoc($result)) {
            if ($admin['language'] != $maillang) {
                $maillang=$admin['language'];
                $lang=load_language($maillang);
                $mail_subject=lang('experiment_calendar').' '.ortime__format($now,'hide_time:true',$maillang);
                $cal_name=lang('experiment_calendar').' '.date("Y-m-d",$now);
                $cal_filename=str_replace(" ","_",$cal_name).".pdf";
                $cal_file=pdfoutput__make_pdf_calendar($now,false,true,$number_of_months,true);
            }
            $mailtext=load_mail("admin_calendar_mailtext",$maillang)."\n".
                        experimentmail__get_admin_footer($maillang,$admin)."\n";
            $message=process_mail_template($mailtext,$admin);
            $done=experimentmail__mail_attach($admin['email'],$from,$mail_subject,$message,$cal_filename,$cal_file);
            if ($done) {
                $i++;
            }
        }
        // reset language
        if ($maillang!=$old_lang) {
            $lang=load_language($old_lang);
        }
        return $cal_name." sent to ".$i." out of ".$rec_count." administrators\n";
    }



    function experimentmail__send_participant_statistics() {
        global $lang, $settings;
        $now=time();
        $from=$settings['support_mail'];
        $headers="From: ".$from."\r\n";

        // remember the current language for later reset
        $old_lang=lang('lang');

        // preload details with current language
        $maillang=$old_lang;
        $statistics=stats__get_textstats_for_email();
        $subject=load_language_symbol('subject_pool_statistics',$maillang).' '.
                        ortime__format($now,'hide_time:true');

        // get experimenters who want to receive the statistics
        $query="SELECT *
            FROM ".table('admin')."
            WHERE get_statistics_mail='y'
            AND disabled='n'
            ORDER BY language";
        $result=or_query($query);

        $i=0;
        $rec_count=pdo_num_rows($result);
        while ($admin = pdo_fetch_assoc($result)) {
            if ($admin['language'] != $maillang) {
                $maillang=$admin['language'];
                $lang=load_language($maillang);
                $statistics=stats__get_textstats_for_email();
                $subject=load_language_symbol('subject_pool_statistics',$maillang).' '.
                ortime__format($now,'hide_time:true',$maillang);
            }
            $mailtext=load_mail("admin_participant_statistics_mailtext",$maillang).
                        "\n\n".$statistics."\n".
            experimentmail__get_admin_footer($maillang,$admin)."\n";
            $message=process_mail_template($mailtext,$admin);
            $done=experimentmail__mail($admin['email'],$subject,$message,$headers);
            if ($done) {
                $i++;
            }
        }
        // reset language
        if ($maillang!=$old_lang) {
            $lang=load_language($old_lang);
        }
        return "statistics sent to ".$i." out of ".$rec_count." administrators\n";
    }

    function experimentmail__bulk_mail_form($experiment_id='',$session_id='',$focus='') {
        global $lang;
        //echo '<A HREF="participants_bulk_mail.php">'.lang('send_mail_to_listed_participants').'</A>';
        $url='participants_bulk_mail.php';
        $pars=array();
        if ($experiment_id!=='') {
            $pars[]='experiment_id='.urlencode($experiment_id);
        }
        if ($session_id!=='') {
            $pars[]='session_id='.urlencode($session_id);
        }
        if ($focus!=='') {
            $pars[]='focus='.urlencode($focus);
        }
        if (count($pars)>0) {
            $url.='?'.implode('&',$pars);
        }
        echo button_link($url,lang('send_mail_to_listed_participants'),"envelope-o");
    }
}


?>
