<?php

namespace OpenProvider\API;

class Response implements ResponseInterface
{
    /**
     * @var array
     */
    private $data;
    /**
     * @var int
     */
    private $total;
    /**
     * @var int
     */
    private $code;
    /**
     * @var string
     */
    private $message;

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data ?? [];
    }

    /**
     * @return int
     */
    public function getTotal(): int
    {
        return $this->total ?? 0;
    }

    /**
     * @return int
     */
    public function getCode(): int
    {
        return $this->code ?? 0;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message ?? '';
    }

    /**
     * @param array $data
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }

    /**
     * @param int $total
     */
    public function setTotal(int $total): void
    {
        $this->total = $total;
    }

    /**
     * @param int $code
     */
    public function setCode(int $code): void
    {
        $this->code = $code;
    }

    /**
     * @param string $message
     */
    public function setMessage(string $message): void
    {
        $this->message = $message;
    }
}
