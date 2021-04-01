<?php

namespace OpenProvider\API;

interface ResponseInterface
{
    /**
     * @param array $data
     */
    public function setData(array $data): void;

    /**
     * @param int $total
     */
    public function setTotal(int $total): void;

    /**
     * @param int $code
     */
    public function setCode(int $code): void;

    /**
     * @param string $message
     */
    public function setMessage(string $message): void;

    /**
     * @return array
     */
    public function getData(): array;

    /**
     * @return int
     */
    public function getTotal(): int;

    /**
     * @return int
     */
    public function getCode(): int;

    /**
     * @return string
     */
    public function getMessage(): string;

    /**
     * @return bool
     */
    public function isSuccess(): bool;
}
