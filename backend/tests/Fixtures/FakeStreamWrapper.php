<?php

namespace Tests\Fixtures;

use Spatie\MediaLibrary\Downloaders\DefaultDownloader;

/**
 * A fake stream wrapper for testing file downloads.
 *
 * This class mocks PHP's native stream wrapper functionality to intercept
 * file downloads in tests. It stores the file content in memory and provides
 * the standard stream operations.
 *
 * @see DefaultDownloader The class that uses this stream wrapper
 */
class FakeStreamWrapper
{
    /**
     * The content to be served when reading from the stream.
     */
    public static string $content;

    /**
     * Current position in the stream.
     */
    private int $position = 0;

    /**
     * The memory stream resource.
     *
     * @var resource
     */
    private $stream;

    /**
     * Opens the stream with the given path and mode.
     *
     * @param  string  $path  The stream path (e.g., 'https://example.com/image.jpg')
     * @param  string  $mode  The mode to open the stream in ('r', 'w', etc.)
     * @param  int  $options  Additional options
     * @param  string|null  $opened_path  The actual opened path
     */
    public function stream_open(string $path, string $mode, int $options, ?string &$opened_path): bool
    {
        $this->position = 0;
        $this->stream = fopen('php://memory', 'r+');

        fwrite($this->stream, static::$content);
        rewind($this->stream);

        return true;
    }

    /**
     * Reads from the stream.
     */
    public function stream_read(int $count): string|false
    {
        $ret = fread($this->stream, $count);

        $this->position += strlen($ret);

        return $ret;
    }

    /**
     * Checks if the end of the stream has been reached.
     */
    public function stream_eof(): bool
    {
        return feof($this->stream);
    }

    /**
     * Gets information about the stream.
     *
     * @return array<string, int>
     */
    public function stream_stat(): array
    {
        return [
            'size' => strlen(static::$content),
        ];
    }

    /**
     * Gets the current position in the stream.
     */
    public function stream_tell(): int
    {
        return $this->position;
    }

    /**
     * Seeks to a position in the stream.
     *
     * @param  int  $offset  The stream offset
     * @param  int  $whence  SEEK_SET, SEEK_CUR, or SEEK_END
     */
    public function stream_seek(int $offset, int $whence = SEEK_SET): bool
    {
        $this->position = $offset;

        return true;
    }
}
