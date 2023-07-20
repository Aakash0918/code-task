<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;


class Auth implements FilterInterface
{
    /**
     * Do whatever processing this filter needs to do.
     * By default it should not return anything during
     * normal execution. However, when an abnormal state
     * is found, it should return an instance of
     * CodeIgniter\HTTP\Response. If it does, script
     * execution will end and that Response will be
     * sent back to the client, allowing for error pages,
     * redirects, etc.
     *
     * @param RequestInterface $request
     * @param array|null       $arguments
     *
     * @return mixed
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        try {
            // Get the authorization header containing the JWT token
            $authorizationHeader = $request->getServer('HTTP_AUTHORIZATION');

            // Extract the token from the authorization header
            if (sscanf($authorizationHeader, 'Bearer %s', $token) !== 1) {
                // Invalid or missing token
                return Services::response()->setStatusCode(401)->setJSON(['status' => false, 'message' => 'Access Denied.']);
            }


            $key = JWT_KEY; // Replace this with your own secret key
            $decodedToken = JWT::decode($token, new Key($key, 'HS256'));
        } catch (\Exception $e) {
            // Invalid token or token validation error
            return Services::response()->setStatusCode(401)->setJSON(['status' => false, 'message' => 'Access Denied.']);
        }

        // Get the user ID from the decoded token
        $userId = $decodedToken->user_id;

        // Set the authenticated user ID in the request attributes
        $request->attributes['user_id'] = $userId;
        $request->attributes['role'] = $decodedToken->role;
    }

    /**
     * Allows After filters to inspect and modify the response
     * object as needed. This method does not allow any way
     * to stop execution of other after filters, short of
     * throwing an Exception or Error.
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param array|null        $arguments
     *
     * @return mixed
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        //
    }
}
