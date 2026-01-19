<?php

namespace helpers\traits;

trait Status
{
    public $recordStatus;

    private $_statusCodeObject;

    /**
     * NEW: Public static method to get ALL codes (case-insensitive access works!)
     */
    public static function getAllStatusCodes(): array
    {
        $instance = new static();
        return $instance->appCodes();
    }
    public  function getStatusCodes(): array
    {
        $instance = new static();
        return $instance->appCodes();
    }
    public function __get($name)
    {
        if ($name === 'statusCode') {
            if ($this->_statusCodeObject === null) {
                $this->_statusCodeObject = new class(static::class) {
                    private array $codes = [];

                    public function __construct(string $modelClass)
                    {
                        // PERFECT: Use public static method - no reflection needed!
                        $this->codes = $modelClass::getAllStatusCodes();
                    }

                    public function __get(string $name)
                    {
                        // Case-insensitive: normalize both to UPPERCASE
                        $normalizedName = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '_', $name));

                        foreach ($this->codes as $code => $info) {
                            $label = $info[0];
                            $constantName = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '_', $label));

                            if ($constantName === $normalizedName) {
                                return $code;
                            }
                        }

                        return null; // Unknown status
                    }
                };
            }
            return $this->_statusCodeObject;
        }

        return parent::__get($name);
    }

    public function loadStatus($code)
    {
        $codes = $this->appCodes();
        $result = $codes[$code] ?? ['unknown code: ' . $code, 'secondary'];

        return [
            'label' => $result[0],
            'theme' => $result[1],
        ];
    }

    /**
     * Keep protected for internal use
     */
    protected function appCodes(): array
    {
        return [
            '0'  => ['False', 'danger'],
            '1'  => ['True', 'success'],
            '2'  => ['Approved', 'success'],
            '3'  => ['Pending', 'info'],
            '4'  => ['Cancelled', 'warning'],
            '5'  => ['Declined', 'danger'],
            '6'  => ['Banned', 'danger'],
            '7'  => ['Suspended', 'warning'],
            '8'  => ['Scheduled', 'info'],
            '9'  => ['Inactive', 'secondary'],
            '10' => ['Active', 'success'],
            '11' => ['Completed', 'success'],
            '12' => ['Processing', 'info'],
            '13' => ['On Hold', 'warning'],
            '14' => ['Failed', 'danger'],
        ];
    }
}
