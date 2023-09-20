<?php

namespace App\View\Helpers;

class SessionMessage {
    const TYPE_SUCCESS = 'success';
    const TYPE_ERROR = 'error';
    const TYPE_WARNING = 'warning';
    const TYPE_INFO = 'info';

    protected function __construct(
        public readonly string $type,
        public readonly string $message,
        public ?int $duration = null
    ) {
    }

    public function forDuration(int $duration = 5): self {
        $this->duration = $duration;
        return $this;
    }

    public function getIcon(): string {
        return match ($this->type) {
            self::TYPE_SUCCESS => 'fa-solid fa-circle-check',
            self::TYPE_ERROR => 'fa-solid fa-circle-xmark',
            self::TYPE_WARNING => 'fa-solid fa-triangle-exclamation',
            self::TYPE_INFO => 'fa-solid fa-circle-info',
            default => 'fa-solid fa-circle-question',
        };
    }

    public static function success(
        string $message,
        ?int $duration = null
    ): self {
        return new self(self::TYPE_SUCCESS, $message, $duration);
    }

    public static function error(string $message, ?int $duration = null): self {
        return new self(self::TYPE_ERROR, $message, $duration);
    }

    public static function warning(
        string $message,
        ?int $duration = null
    ): self {
        return new self(self::TYPE_WARNING, $message, $duration);
    }

    public static function info(string $message, ?int $duration = null): self {
        return new self(self::TYPE_INFO, $message, $duration);
    }
}
