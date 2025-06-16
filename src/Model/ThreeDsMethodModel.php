<?php

namespace App\Model;

class ThreeDsMethodModel
{

    private string $threeDsMethodURL;
    private string $threeDSMethodData;


    public function getThreeDsMethodURL(): string
    {
        return $this->threeDsMethodURL;
    }

    public function setThreeDsMethodURL(string $threeDsMethodURL): ThreeDsMethodModel
    {
        $this->threeDsMethodURL = $threeDsMethodURL;
        return $this;
    }

    public function getThreeDSMethodData(): string
    {
        return $this->threeDSMethodData;
    }

    public function setThreeDSMethodData(string $threeDSMethodData): ThreeDsMethodModel
    {
        $this->threeDSMethodData = $threeDSMethodData;
        return $this;
    }


}