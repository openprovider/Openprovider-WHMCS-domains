<?php


namespace OpenProvider\WhmcsRegistrar\helpers;

use OpenProvider\WhmcsRegistrar\enums\FileOpenModeType;

class CSV
{
    const HeaderRequired = 'required';
    const HeaderOptional = 'optional';

    const WriteHeaders = true;
    const NoWriteHeaders = false;

    protected $file;

    protected $filepath;

    protected $delimiter;

    protected $mode;

    protected $headers;

    protected $size;

    protected $newFile;

    public function __construct(string $filepath, string $mode = FileOpenModeType::Read, bool $newFile = true)
    {
        $this->filepath  = $filepath;
        $this->mode      = $mode;
        $this->delimiter = ',';
        $this->newFile   = $newFile;
    }

    /**
     * Method open file stream.
     * File took from class constructor.
     *
     * @return bool
     */
    public function open(): bool
    {
        if ($this->file = fopen($this->filepath, $this->mode)) {
            $this->size = filesize($this->file);
            return true;
        }
        return false;
    }

    /**
     * Method parse csv file and return array with all records.
     *
     * @return array
     */
    public function getRecords(): array
    {
        if (!$this->file) return [];

        $result = [];
        if (!count($this->headers))
            $this->setHeaders();
        while (($row = fgetcsv($this->file, $this->size, $this->delimiter)) !== false)
        {
            $fieldsCount = count($row);
            $tmp = [];
            for ($i = 0; $i < $fieldsCount; $i++) {
                $tmp[$this->headers[$i]] = $row[$i];
            }
            $result[] = $tmp;
        }
        return $result;
    }

    /**
     * Method write records from array to file.
     * Writing mode define by setMode method or in class constructor.
     *
     * @param array $records
     * @param bool $writeHeaders
     * @return bool
     */
    public function writeRecords(array $records = [], bool $writeHeaders = self::WriteHeaders): bool
    {
        if (!$this->file) return false;

        if ($writeHeaders == self::WriteHeaders)
            fputcsv($this->file, $this->headers);

        foreach ($records as $record) {
            fputcsv($this->file, $record);
        }

        return true;
    }

    /**
     * Method close file stream.
     *
     * @return bool
     */
    public function close(): bool
    {
        if ($this->file)
            return fclose($this->file);
        return false;
    }

    /**
     * Method set headers to csv file.
     *
     * @param array $headers
     */
    public function setHeaders(array $headers = []): void
    {
        if (count($headers) < 1) {
            if (!$this->file) return;

            fseek($this->file, 0, SEEK_SET);
            $this->headers = fgetcsv($this->file, $this->size, $this->delimiter);
            return;
        }
        $this->headers = $headers;
    }

    /**
     * Method return headers array.
     *
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Method check headers with array of needle headers.
     * Key of needleArray is header names. Value is option.
     * Options list placed in CSV class
     *
     * @param array $needleArray
     * @return array
     */
    public function checkHeaders(array $needleArray = []): array
    {
        $result = [
            'success'        => true,
            'missingHeaders' => [],
        ];

        if (count($this->headers) < 1) {
            $result['success']        = false;
            $result['missingHeaders'] = $needleArray;
            return $result;
        }

        $requiredHeaders = array_keys(array_filter($needleArray, function ($value) {
            if ($value == CSV::HeaderRequired)
                return true;
            return false;
        }));

        foreach ($requiredHeaders as $headerItem) {
            if (!in_array($headerItem, $this->headers)) {
                $result['missingHeaders'][] = $headerItem;
            }
        }

        if (count($result['missingHeaders']) > 0) {
            $result['success'] = false;
            return $result;
        }

        return $result;
    }

    /**
     * Method to set open file mode.
     * File open mods placed in <module>/enums/FileOpenModeType.php
     *
     * @param string $mode
     */
    public function setMode(string $mode): void
    {
        $this->mode = $mode;
    }

    /**
     * Method to set delimiter in csv file.
     *
     * @param string $delimiter
     */
    public function setDelimiter(string $delimiter = ','): void
    {
        $this->delimiter = $delimiter;
    }

    /**
     * Method return filepath.
     *
     * @return string
     */
    public function getFilepath(): string
    {
        return $this->filepath;
    }
}