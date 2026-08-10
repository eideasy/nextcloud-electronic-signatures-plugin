<?php

namespace OCA\ElectronicSignatures\Signature;

class SignatureParameters
{
    /** @var string|null */
    private $signerName;

    /** @var string|null */
    private $contactInfo;

    /** @var string|null */
    private $location;

    /** @var string|null */
    private $reason;

    public function __construct($reason = null, $signerName = null, $contactInfo = null, $location = null)
    {
        $this->signerName = $signerName;
        $this->contactInfo = $contactInfo;
        $this->location = $location;
        $this->reason = $reason;
    }

    public function getSignerName()
    {
        return $this->signerName;
    }

    public function setSignerName(string $signerName): void
    {
        $this->signerName = $signerName;
    }

    public function getContactInfo()
    {
        return $this->contactInfo;
    }

    public function setContactInfo(string $contactInfo): void
    {
        $this->contactInfo = $contactInfo;
    }

    public function getLocation()
    {
        return $this->location;
    }

    public function setLocation(string $location): void
    {
        $this->location = $location;
    }

    public function getReason()
    {
        return $this->reason;
    }

    public function setReason(string $reason): void
    {
        $this->reason = $reason;
    }
}
