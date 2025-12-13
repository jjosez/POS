<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2022 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\POS\Lib;

use FacturaScripts\Core\Response;
use FacturaScripts\Core\Tools;

/**
 * Service for building and sending HTTP responses for Point of Sale operations.
 */
class PointOfSaleResponseBuilder
{
    private BasePointOfSaleController $controller;
    private array $responseData = [];
    private ?string $token = null;

    public function __construct(BasePointOfSaleController $controller)
    {
        $this->controller = $controller;
    }

    /**
     * Gets the current response object (lazy loading).
     * This ensures we always have the latest response even if extensions modify it.
     */
    private function getResponse(): Response
    {
        return $this->controller->getResponseObject();
    }

    /**
     * Sets the response token.
     */
    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    /**
     * Adds data to the response.
     */
    public function addResponseData(array $data = []): void
    {
        $this->responseData = array_merge($this->responseData, $data);
    }

    /**
     * Sets a success response with data.
     */
    public function setSuccessResponse(array $data = []): void
    {
        $this->responseData['status'] = 'success';
        $this->responseData['data'] = $data;
    }

    /**
     * Builds the complete response including messages and token.
     */
    public function buildResponse(array $data = []): void
    {
        $response = array_merge($data, $this->responseData);

        $response['messages'] = $this->getMessages();
        $response['token'] = $this->token;

        $this->setResponse($response);
    }

    /**
     * Sends a response to the client.
     */
    public function setResponse($content, bool $encode = true): void
    {
        $response = $this->getResponse();

        if ($encode) {
            $response->json($content);
            return;
        }

        $response->setContent($content);
    }

    /**
     * Reads log messages from the system.
     */
    private function getMessages(): array
    {
        $messages = [];
        $levels = ['critical', 'warning', 'notice', 'info', 'error'];

        $logs = array_merge(
            Tools::log()->read('master', $levels),
            Tools::log()->read('POS', $levels)
        );

        foreach ($logs as $log) {
            $type = match ($log['level']) {
                'critical', 'warning', 'error' => 'warning',
                'notice' => 'success',
                default => 'info'
            };

            $messages[] = ['type' => $type, 'message' => $log['message']];
        }

        return $messages;
    }

    /**
     * Clears the response data.
     */
    public function clearResponseData(): void
    {
        $this->responseData = [];
    }
}
