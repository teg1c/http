<?php

namespace tegic;

use RuntimeException;
use tegic\Enums\ErrorCode;
use tegic\Model\HttpOptions;
use tegic\Model\HttpResponse;
use tegic\Traits\ClientTrait;

class Http
{
    use ClientTrait;
    public function __construct()
    {
        $this->setHasSelfSignedCertificate(true);
    }

    public function request(string $method, string $uri, HttpOptions|array $options = []): HttpResponse
    {
        $CurlHandle = Middleware::create_curl_handler($method, $uri, $options);
        if (!$CurlHandle) {
            throw new RuntimeException(
                'An error occurred while creating the curl handler'
            );
        }

        $result = new HttpResponse();
        $result->setCurlHandle($CurlHandle);

        $response = curl_exec($CurlHandle);
        if (curl_errno($CurlHandle) || !$response) {
            $result->setErrorCode(curl_errno($CurlHandle));
            $result->setErrorMessage(
                curl_error($CurlHandle) ??
                ErrorCode::getMessage(curl_errno($CurlHandle))
            );
            return $result;
        }

        $result->setStatusCode(curl_getinfo($CurlHandle, CURLINFO_HTTP_CODE));
        $result->setHeaderSize(curl_getinfo($CurlHandle, CURLINFO_HEADER_SIZE));
        $result->setHeaders(substr((string) $response, 0, $result->getHeaderSize()));
        $result->setBody(substr((string) $response, $result->getHeaderSize()));

        curl_close($CurlHandle);

        return $result;
    }
    /**
     * Set has self-signed certificate
     *
     * This is used to set the curl option CURLOPT_SSL_VERIFYPEER
     * and CURLOPT_SSL_VERIFYHOST to false. This is useful when you are
     * in local environment, or you have self-signed certificate.
     *
     * @param bool $has
     *
     * @return void
     */
    public function setHasSelfSignedCertificate(bool $has): void
    {
        putenv('HAS_SELF_SIGNED_CERT='.($has ? 'true' : 'false'));
    }
}
