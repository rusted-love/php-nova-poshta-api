<?php

declare(strict_types=1);

namespace BladL\NovaPoshta;

use BladL\NovaPoshta\Exceptions\CurlException;
use BladL\NovaPoshta\Exceptions\ErrorResultException;
use BladL\NovaPoshta\Exceptions\JsonEncodeException;
use BladL\NovaPoshta\Exceptions\JsonParseException;
use BladL\NovaPoshta\Exceptions\QueryFailedException;
use BladL\NovaPoshta\Results\ResultContainer;
use DateTimeZone;
use Exception;
use function is_bool;
use JsonException;
use Psr\Log\LoggerInterface;
use stdClass;

abstract class APIFetcher
{
    private const TIMEZONE = 'Europe/Kiev';

    final public static function getTimeZone(): DateTimeZone
    {
        return new DateTimeZone(self::TIMEZONE);
    }

    private string $apiKey;

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    private ?LoggerInterface $logger = null;

    final public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @throws CurlException
     * @throws QueryFailedException
     */
    public function execute(string $model, string $method, array $params): ResultContainer
    {
        $logger = $this->logger;
        try {
            $payload = json_encode([
                'apiKey' => $this->apiKey,
                'modelName' => $model,
                'calledMethod' => $method,
                'methodProperties' => empty($params) ? new stdClass() : $params,
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
            if ($logger) {
                $encoded_params = json_encode($params, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
                $logger->info("Called $model->$method($encoded_params)");
            }
            if (false === $payload) {
                throw new JsonEncodeException(new Exception('Returned payload is false'));
            }
        } /* @noinspection PhpUndefinedClassInspection */
        catch (JsonException $e) {
            throw new JsonEncodeException($e);
        }
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://api.novaposhta.ua/v2.0/json/',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2_0,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => ['content-type: application/json'],
        ]);
        $result = curl_exec($curl);
        $err = curl_error($curl);
        $err_no = curl_errno($curl);
        curl_close($curl);
        if ($err || $err_no || is_bool($result)) {
            if ($logger) {
                $logger->alert("Curl response error #$err_no, $err. Response: '$result'.");
            }
            throw new CurlException($err, $err_no);
        }
        if ($logger) {
            $logger->debug("Result: $result");
        }
        try {
            $resp = json_decode($result, true, 512, JSON_THROW_ON_ERROR);
        } /* @noinspection PhpUndefinedClassInspection */
        catch (JsonException $e) {
            if ($logger) {
                $logger->critical("Bad response returned. Result: '$result'.", [
                    'exception' => $e,
                ]);
            }
            throw new JsonParseException($result, $e);
        }
        if (isset($resp['errors'])) {
            $errors = $resp['errors'];
            if (!empty($errors)) {
                if ($logger) {
                    $error = implode(',', $errors);
                    $logger->error("Error returned. Description: '$error'.");
                }
                throw new ErrorResultException($resp['errors']);
            }
        }

        return new ResultContainer($resp);
    }
}
