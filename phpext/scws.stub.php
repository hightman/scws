<?php

/** @generate-class-entries */

final class SimpleCWS
{
    /**
     * @readonly
     * @var resource
     */
    public $handle;

    /**
     * @tentative-return-type
     * @alias scws_close
     */
    public function close(): void {}

    /**
     * @tentative-return-type
     * @alias scws_add_dict
     */
    public function add_dict(string $dict_path, int $mode = SCWS_XDICT_XDB): bool {}

    /**
     * @tentative-return-type
     * @alias scws_set_charset
     */
    public function set_charset(string $charset): bool {}

    /**
     * @tentative-return-type
     * @alias scws_set_dict
     */
    public function set_dict(string $dict_path, int $mode = SCWS_XDICT_XDB): bool {}

    /**
     * @tentative-return-type
     * @alias scws_set_rule
     */
    public function set_rule(string $rule_path): bool {}

    /**
     * @tentative-return-type
     * @alias scws_set_ignore
     */
    public function set_ignore(bool $yes): bool {}

    /**
     * @tentative-return-type
     * @alias scws_set_multi
     */
    public function set_multi(int $multi): bool {}

    /**
     * @tentative-return-type
     * @alias scws_set_duality
     */
    public function set_duality(bool $yes): bool {}

    /**
     * @tentative-return-type
     * @alias scws_send_text
     */
    public function send_text(string $text): bool {}

    /**
     * @return array<int, array>
     * @alias scws_get_result
     */
    public function get_result(): array {}

    /**
     * @return array<int, array>
     * @alias scws_get_tops
     */
    public function get_tops(int $limit = 10, ?string $attr = null): array {}

    /**
     * @tentative-return-type
     * @alias scws_has_word
     */
    public function has_word(string $attr): bool {}

    /**
     * @return array<int, array>
     * @alias scws_get_words
     */
    public function get_words(string $attr): array {}

    /**
     * @tentative-return-type
     * @alias scws_version
     */
    public function version(): string {}
}

/**
 * @return resource|false
 * @refcount 1
 */
function scws_open() {} 

/** @refcount 1 */
function scws_new(): SimpleCWS|false {}

/**
 * @param resource $handle
 */
function scws_close($handle): void {}

/**
 * @param resouce $handle
 */
function scws_add_dict($handle, string $dict_path, int $mode = SCWS_XDICT_XDB): bool {}

/**
 * @param resouce $handle
 */
function scws_set_charset($handle, string $charset): bool {}

/**
 * @param resouce $handle
 */
function scws_set_dict($handle, string $dict_path, int $mode = SCWS_XDICT_XDB): bool {}

/**
 * @param resouce $handle
 */
function scws_set_rule($handle, string $rule_path): bool {}

/**
 * @param resouce $handle
 */
function scws_set_ignore($handle, bool $yes): bool {}

/**
 * @param resouce $handle
 */
function scws_set_multi($handle, int $multi): bool {}

/**
 * @param resouce $handle
 */
function scws_set_duality($handle, bool $yes): bool {}

/**
 * @param resouce $handle
 */
function scws_send_text($handle, string $text): bool {}

/**
 * @param resouce $handle
 * @return array<int, array>
 */
function scws_get_result($handle): array {}

/**
 * @param resouce $handle
 * @return array<int, array>
 */
function scws_get_tops($handle, int $limit = 10, ?string $attr = null): array {}

/**
 * @param resouce $handle
 */
function scws_has_word($handle, string $attr): bool {}

/**
 * @param resouce $handle
 * @return array<int, array>
 */
function scws_get_words($handle, string $attr): array {}

/** @refcount 1 */
function scws_version(): string {}

