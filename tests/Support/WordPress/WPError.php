<?php

declare(strict_types=1);

// phpcs:ignoreFile -- Compatibility stub mirrors WordPress core naming.

if (!class_exists('WP_Error')) {
    final class WP_Error
    {
        /**
         * @var array<string, list<string>>
         */
        private array $errors = [];

        public function __construct(
            string $code = '',
            string $message = '',
        ) {
            if ($code !== '') {
                $this->add($code, $message);
            }
        }

        public function add(string $code, string $message): void
        {
            $this->errors[$code][] = $message;
        }

        public function has_errors(): bool
        {
            return $this->errors !== [];
        }

        public function get_error_code(): string
        {
            return array_key_first($this->errors) ?? '';
        }

        public function get_error_message(): string
        {
            $code = $this->get_error_code();

            return $code === '' ? '' : ($this->errors[$code][0] ?? '');
        }
    }
}
