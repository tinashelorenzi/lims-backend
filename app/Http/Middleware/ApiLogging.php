<?php

namespace App\Http\Middleware;

use App\Models\ApiLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ApiLogging
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        $requestId = Str::uuid()->toString();
        
        // Add request ID to the request for tracing
        $request->headers->set('X-Request-ID', $requestId);
        
        $response = $next($request);
        
        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        
        // Log the API request asynchronously to avoid performance impact
        $this->logApiRequest($request, $response, $responseTime, $requestId);
        
        return $response;
    }

    /**
     * Log the API request
     */
    private function logApiRequest(Request $request, Response $response, float $responseTime, string $requestId): void
    {
        try {
            // Get request data
            $requestBody = $this->getRequestBody($request);
            $responseBody = $this->getResponseBody($response);
            
            // Sanitize sensitive data
            $requestBody = $this->sanitizeSensitiveData($requestBody);
            $responseBody = $this->sanitizeSensitiveData($responseBody);
            
            ApiLog::create([
                'user_id' => Auth::id(),
                'method' => $request->method(),
                'endpoint' => $request->getRequestUri(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'request_headers' => $this->sanitizeHeaders($request->headers->all()),
                'request_body' => $requestBody,
                'response_headers' => $this->sanitizeHeaders($response->headers->all()),
                'response_body' => $responseBody,
                'response_status' => $response->getStatusCode(),
                'response_time' => $responseTime,
                'session_id' => $request->session()?->getId(),
                'request_id' => $requestId,
                'error_type' => $this->getErrorType($response),
                'error_message' => $this->getErrorMessage($response),
                'logged_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Silently fail to avoid breaking the API response
            \Log::error('Failed to log API request', [
                'error' => $e->getMessage(),
                'request_id' => $requestId,
            ]);
        }
    }

    /**
     * Get request body data
     */
    private function getRequestBody(Request $request): ?array
    {
        try {
            if ($request->isMethod('GET')) {
                return $request->query->all();
            }
            
            $content = $request->getContent();
            if (empty($content)) {
                return null;
            }
            
            // Try to decode JSON
            $decoded = json_decode($content, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
            
            // If not JSON, try to get input
            return $request->all();
        } catch (\Exception $e) {
            return ['error' => 'Failed to parse request body: ' . $e->getMessage()];
        }
    }

    /**
     * Get response body data
     */
    private function getResponseBody(Response $response): ?array
    {
        try {
            $content = $response->getContent();
            
            if (empty($content)) {
                return null;
            }
            
            // Only log JSON responses to avoid huge data
            if (str_contains($response->headers->get('Content-Type', ''), 'application/json')) {
                $decoded = json_decode($content, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $decoded;
                }
            }
            
            // For non-JSON responses, just log a summary
            return [
                'type' => $response->headers->get('Content-Type'),
                'size' => strlen($content),
                'summary' => substr($content, 0, 200),
            ];
        } catch (\Exception $e) {
            return ['error' => 'Failed to parse response body: ' . $e->getMessage()];
        }
    }

    /**
     * Sanitize sensitive data from request/response
     */
    private function sanitizeSensitiveData(?array $data): ?array
    {
        if (!$data) {
            return $data;
        }
        
        $sensitiveKeys = [
            'password',
            'password_confirmation',
            'token',
            'api_key',
            'secret',
            'authorization',
            'auth',
            'access_token',
            'refresh_token',
            'bearer',
            'csrf_token',
            '_token',
        ];
        
        return $this->recursiveSanitize($data, $sensitiveKeys);
    }

    /**
     * Recursively sanitize sensitive data
     */
    private function recursiveSanitize(array $data, array $sensitiveKeys): array
    {
        foreach ($data as $key => $value) {
            $lowerKey = strtolower($key);
            
            // Check if key contains sensitive information
            foreach ($sensitiveKeys as $sensitiveKey) {
                if (str_contains($lowerKey, $sensitiveKey)) {
                    $data[$key] = '[REDACTED]';
                    continue 2;
                }
            }
            
            // Recursively sanitize nested arrays
            if (is_array($value)) {
                $data[$key] = $this->recursiveSanitize($value, $sensitiveKeys);
            }
        }
        
        return $data;
    }

    /**
     * Sanitize headers
     */
    private function sanitizeHeaders(array $headers): array
    {
        $sanitizedHeaders = [];
        
        foreach ($headers as $key => $value) {
            $lowerKey = strtolower($key);
            
            if (in_array($lowerKey, ['authorization', 'cookie', 'x-api-key', 'x-auth-token'])) {
                $sanitizedHeaders[$key] = '[REDACTED]';
            } else {
                $sanitizedHeaders[$key] = $value;
            }
        }
        
        return $sanitizedHeaders;
    }

    /**
     * Get error type from response
     */
    private function getErrorType(Response $response): ?string
    {
        $statusCode = $response->getStatusCode();
        
        if ($statusCode >= 400 && $statusCode < 500) {
            return 'Client Error';
        } elseif ($statusCode >= 500) {
            return 'Server Error';
        }
        
        return null;
    }

    /**
     * Get error message from response
     */
    private function getErrorMessage(Response $response): ?string
    {
        if ($response->getStatusCode() < 400) {
            return null;
        }
        
        try {
            $content = $response->getContent();
            $decoded = json_decode($content, true);
            
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded['message'] ?? $decoded['error'] ?? 'Unknown error';
            }
            
            return substr($content, 0, 500);
        } catch (\Exception $e) {
            return 'Failed to extract error message: ' . $e->getMessage();
        }
    }
}