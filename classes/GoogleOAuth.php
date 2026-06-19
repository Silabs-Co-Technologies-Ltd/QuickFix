<?php
/**
 * Google OAuth Handler Class
 * Handles Google Sign-In authentication and token verification
 */

class GoogleOAuth {
    private $clientId;
    private $clientSecret;
    private $redirectUrl;
    private $db;

    public function __construct($clientId, $clientSecret, $redirectUrl, $database) {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->redirectUrl = $redirectUrl;
        $this->db = $database;
    }

    /**
     * Verify Google ID Token
     * This verifies the JWT token from Google's Sign-In button
     */
    public function verifyIdToken($token) {
        try {
            // Fetch Google's public certificates
            $certsUrl = 'https://www.googleapis.com/oauth2/v1/certs';
            $certs = $this->fetchCerts($certsUrl);

            if (!$certs) {
                return ['success' => false, 'message' => 'Failed to fetch Google certificates'];
            }

            // Decode the JWT token
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                return ['success' => false, 'message' => 'Invalid token format'];
            }

            // Decode header and payload
            $header = json_decode(base64_decode(strtr($parts[0], '-_', '+/')), true);
            $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);

            if (!$header || !$payload) {
                return ['success' => false, 'message' => 'Failed to decode token'];
            }

            // Verify the signature
            $kid = $header['kid'] ?? null;
            if (!$kid || !isset($certs[$kid])) {
                return ['success' => false, 'message' => 'Invalid key ID'];
            }

            // Verify token signature
            $publicKey = $certs[$kid];
            $signature = base64_decode(strtr($parts[2], '-_', '+/'));
            $data = $parts[0] . '.' . $parts[1];

            // Verify using OpenSSL
            $verified = openssl_verify($data, $signature, $publicKey, OPENSSL_ALGO_SHA256);

            if ($verified !== 1) {
                return ['success' => false, 'message' => 'Invalid token signature'];
            }

            // Verify token claims
            if ($payload['aud'] !== $this->clientId) {
                return ['success' => false, 'message' => 'Invalid audience'];
            }

            if ($payload['exp'] < time()) {
                return ['success' => false, 'message' => 'Token expired'];
            }

            return ['success' => true, 'payload' => $payload];
        } catch (Exception $e) {
            error_log('Google OAuth verification error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Token verification failed'];
        }
    }

    /**
     * Fetch Google's public certificates
     */
    private function fetchCerts($url) {
        try {
            $context = stream_context_create([
                'http' => [
                    'timeout' => 5,
                    'user_agent' => 'QuickFix-OAuth/1.0'
                ]
            ]);

            $response = @file_get_contents($url, false, $context);

            if ($response === false) {
                return null;
            }

            $certs = json_decode($response, true);

            if (!$certs) {
                return null;
            }

            // Convert PEM certificates to OpenSSL format
            $formattedCerts = [];
            foreach ($certs as $kid => $cert) {
                $formattedCerts[$kid] = "-----BEGIN CERTIFICATE-----\n" .
                                       chunk_split($cert, 64, "\n") .
                                       "-----END CERTIFICATE-----\n";
            }

            return $formattedCerts;
        } catch (Exception $e) {
            error_log('Failed to fetch Google certs: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Handle Google Sign-In callback
     * Creates or updates user account based on Google profile
     */
    public function handleCallback($idToken, $userModel) {
        // Verify the token
        $verification = $this->verifyIdToken($idToken);

        if (!$verification['success']) {
            return ['success' => false, 'message' => $verification['message']];
        }

        $payload = $verification['payload'];

        // Extract user information from payload
        $googleId = $payload['sub'];
        $email = $payload['email'];
        $name = $payload['name'] ?? 'Google User';
        $picture = $payload['picture'] ?? null;

        // Check if user exists with this Google ID
        $existingUser = $this->getUserByGoogleId($googleId);

        if ($existingUser) {
            // User already exists, return their data
            return [
                'success' => true,
                'message' => 'Login successful!',
                'user' => $existingUser,
                'is_new' => false
            ];
        }

        // Check if email already exists
        $emailUser = $userModel->getUserByEmail($email);

        if ($emailUser) {
            // Email exists but not linked to Google
            // Link the Google account to existing user
            $this->linkGoogleAccount($emailUser['id'], $googleId, $picture);
            unset($emailUser['password']);
            return [
                'success' => true,
                'message' => 'Google account linked successfully!',
                'user' => $emailUser,
                'is_new' => false
            ];
        }

        // Create new user account
        $result = $userModel->register($name, $email, bin2hex(random_bytes(16)), 'customer');

        if (!$result['success']) {
            return ['success' => false, 'message' => 'Failed to create account'];
        }

        // Link Google account to new user
        $this->linkGoogleAccount($result['user_id'], $googleId, $picture);

        // Retrieve the newly created user
        $newUser = $userModel->getUserById($result['user_id']);

        return [
            'success' => true,
            'message' => 'Account created and linked successfully!',
            'user' => $newUser,
            'is_new' => true
        ];
    }

    /**
     * Get user by Google ID
     */
    private function getUserByGoogleId($googleId) {
        $query = "SELECT id, name, email, role, created_at FROM users WHERE google_id = ? LIMIT 1";
        $params = [$googleId];
        $types = 's';

        return $this->db->fetchOne($query, $params, $types);
    }

    /**
     * Link Google account to user
     */
    private function linkGoogleAccount($userId, $googleId, $picture = null) {
        $query = "UPDATE users SET google_id = ?, profile_picture = ?, updated_at = NOW() WHERE id = ?";
        $params = [$googleId, $picture, $userId];
        $types = 'ssi';

        return $this->db->update($query, $params, $types);
    }

    /**
     * Generate Google OAuth authorization URL
     */
    public function getAuthorizationUrl($state = null) {
        if (!$state) {
            $state = bin2hex(random_bytes(16));
        }

        $params = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUrl,
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state
        ];

        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
    }

    /**
     * Exchange authorization code for access token
     */
    public function getAccessToken($code) {
        try {
            $params = [
                'code' => $code,
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'redirect_uri' => $this->redirectUrl,
                'grant_type' => 'authorization_code'
            ];

            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => 'Content-Type: application/x-www-form-urlencoded',
                    'content' => http_build_query($params),
                    'timeout' => 5
                ]
            ]);

            $response = @file_get_contents('https://oauth2.googleapis.com/token', false, $context);

            if ($response === false) {
                return ['success' => false, 'message' => 'Failed to get access token'];
            }

            $data = json_decode($response, true);

            if (isset($data['error'])) {
                return ['success' => false, 'message' => $data['error_description'] ?? 'OAuth error'];
            }

            return ['success' => true, 'data' => $data];
        } catch (Exception $e) {
            error_log('OAuth token exchange error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Token exchange failed'];
        }
    }
}
?>
