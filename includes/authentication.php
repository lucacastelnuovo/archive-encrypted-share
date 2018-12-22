<?php

$auth_authorization_url = 'https://accounts.lucacastelnuovo.nl/auth/authorize';
$auth_access_token_url = 'https://accounts.lucacastelnuovo.nl/auth/token';

$auth_api_user = 'https://api.lucacastelnuovo.nl/user/';
$auth_api_token = 'https://api.lucacastelnuovo.nl/token/';


/* Helper Functions */

function auth_gen_state($length) {
    return bin2hex(random_bytes($length / 2));
}


function auth_request($url, $data) {
    $curl = curl_init();

    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $result = curl_exec($curl);
    curl_close($curl);
    $result = json_decode($result, true);

    return $result;
}


/* Auth Functions */

function auth_get_authorization_code($client_id, $scope, $redirect_uri = null) {
    if (empty($client_id)) {
        throw new Exception('client_id empty');
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION['auth_state'] = auth_gen_state(32);
        header("Location: {$auth_authorization_url}?client_id={$client_id}&scope={$scope}&state={$_SESSION['auth_state']}&redirect_uri={$redirect_uri}");
    } else {
        header("Location: {$auth_authorization_url}?client_id={$client_id}&scope={$scope}&redirect_uri={$redirect_uri}");
    }

    exit;
}


function auth_get_access_token($client_id, $client_secret, $code, $provided_state) {
    if (empty($client_id)) {
        throw new Exception('client_id empty');
    }

    if (empty($client_secret)) {
        throw new Exception('client_secret empty');
    }

    if (empty($code)) {
        throw new Exception('code empty');
    }

    if (empty($state)) {
        throw new Exception('state empty');
    }

    if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['auth_state'])) {
        if ($_SESSION['auth_state'] != $provided_state) {
            throw new Exception('invalid state');
        }
    }

    $access_token_request = auth_request($auth_access_token_url, ["client_id" => "{$client_id}", "client_secret" => "{$client_secret}", "code" => "{$code}", "state" => "{$provided_state}"]);

    if (!$access_token_request['success']) {
        throw new Exception($access_token_request['error']);
    }

    return $access_token_request;
}


/* API Functions */

function api_get_user($access_token) {
    if (empty($access_token)) {
        throw new Exception('access_token empty');
    }

    $user_request = json_decode(file_get_contents("$auth_api_user?access_token={$access_token}"));

    if (!$user_request['success']) {
        throw new Exception($user_request['error']);
    }

    return $user_request;
}


function api_get_token($access_token) {
    if (empty($access_token)) {
        throw new Exception('access_token empty');
    }

    $token_request = json_decode(file_get_contents("$auth_api_token?access_token={$access_token}"));

    if (!$token_request['success']) {
        throw new Exception($token_request['error']);
    }

    return $token_request;
}