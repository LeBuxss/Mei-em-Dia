<?php

declare(strict_types=1);

namespace App\Core;

final class Response
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        private readonly string $body,
        private readonly int $statusCode = 200,
        private readonly array $headers = []
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function json(
        array $data,
        int $statusCode = 200,
        array $headers = []
    ): self {
        $body = json_encode(
            $data,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR
        );

        return new self(
            $body,
            $statusCode,
            ['Content-Type' => 'application/json; charset=utf-8'] + $headers
        );
    }

    /**
     * @param array<string, string> $headers
     */
    public static function html(
        string $html,
        int $statusCode = 200,
        array $headers = []
    ): self {
        return new self(
            $html,
            $statusCode,
            ['Content-Type' => 'text/html; charset=utf-8'] + $headers
        );
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    public function body(): string
    {
        return $this->body;
    }

    /**
     * @return array<string, string>
     */
    public function headers(): array
    {
        return $this->headers;
    }

    public function send(): void
    {
        http_response_code($this->statusCode);

        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value);
        }

        echo $this->body;
    }
}
