<?php
/**
 * @author magefast@gmail.com www.magefast.com
 */

namespace Strekoza\ImportTool\Service;

class Report
{
    /**
     * @var array
     */
    private $errors = [];

    /**
     * @var array
     */
    private $notices = [];

    /**
     * @var array
     */
    private $criticalErrors = [];

    /**
     * @param string $error
     */
    public function setError(string $error)
    {
        if (!empty($error)) {
            $this->errors[] = $error;
        }
    }

    /**
     * @param string $error
     */
    public function setCriticalError(string $error)
    {
        if (!empty($error)) {
            $this->criticalErrors[] = $error;
        }
    }

    /**
     * @param string $notice
     */
    public function setNotice(string $notice)
    {
        if (!empty($notice)) {
            $this->notices[] = $notice;
        }
    }

    /**
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @return array
     */
    public function getCriticalErrors(): array
    {
        return $this->criticalErrors;
    }

    /**
     * @return array
     */
    public function getNotices(): array
    {
        return $this->notices;
    }
}