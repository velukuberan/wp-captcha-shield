<?php

declare(strict_types=1);

namespace WpCaptchaShield\WordPress\Forms\Comments;

final class CommentFormRegistrar
{
    public function __construct(
        private readonly CommentFormIntegration $integration,
    ) {
    }

    public function registerHooks(): void
    {
        add_action(
            'wp_enqueue_scripts',
            [$this->integration, 'enqueue'],
        );

        add_filter(
            'comment_form_submit_field',
            [$this->integration, 'addWidgetToSubmitField'],
        );

        add_action(
            'pre_comment_on_post',
            [$this->integration, 'validate'],
            30,
            1,
        );
    }
}
