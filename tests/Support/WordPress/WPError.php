<?php

declare(strict_types=1);

// phpcs:ignoreFile -- Compatibility stub mirrors WordPress core naming.

if (!class_exists('WP_Error')) {
    final class WP_Error
    {
        public function __construct(
            private readonly string $code = '',
            private readonly string $message = '',
        ) {
        }

        public function get_error_code(): string
        {
            return $this->code;
        }

        public function get_error_message(): string
        {
            return $this->message;
        }
    }
}