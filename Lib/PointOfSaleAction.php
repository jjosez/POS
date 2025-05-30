<?php

namespace FacturaScripts\Plugins\POS\Lib;

class PointOfSaleAction
{
    protected const DEFAULT_ICON = 'fa-solid fa-circle-dot';
    protected const ACTION_TYPE_LINK = 'link';
    protected const ACTION_TYPE_REDIRECT = 'redirect';
    protected const ACTION_TYPE_ACTION = 'action';

    protected string $actionName;
    protected string $actionTitle;
    protected string $actionIcon;
    protected string $actionType;
    protected ?string $actionController;
    protected ?array $params;

    private function __construct(
        string $actionName,
        string $actionTitle,
        ?string $actionIcon = self::DEFAULT_ICON,
        ?string $actionType = self::ACTION_TYPE_ACTION,
        ?array $params = null,
        ?string $actionController = null
    )
    {
        $this->actionName = $actionName;
        $this->actionTitle = $actionTitle;
        $this->actionIcon = $actionIcon;
        $this->actionType = $actionType;
        $this->actionController = $actionController;
        $this->params = $params ?? [];
    }

    public static function createAction(
        string $actionName,
        string $actionTitle,
        ?string $actionIcon = null,
        ?array $params = null
    ): self
    {
        return new self(
            $actionName,
            $actionTitle,
            $actionIcon ?? self::DEFAULT_ICON,
            self::ACTION_TYPE_ACTION,
            $params
        );
    }

    public static function createLink(
        string $actionName,
        string $actionTitle,
        ?string $actionIcon = null,
        ?string $actionController = null,
        ?array $params = null
    ): self
    {
        return new self(
            $actionName,
            $actionTitle,
            $actionIcon ?? self::DEFAULT_ICON,
            self::ACTION_TYPE_LINK,
            $params,
            $actionController
        );
    }

    public static function createRedirect(
        string $actionName,
        string $actionTitle,
        ?string $actionIcon = null,
        ?string $actionController = null,
        ?array $params = null
    ): self
    {
        return new self(
            $actionName,
            $actionTitle,
            $actionIcon ?? self::DEFAULT_ICON,
            self::ACTION_TYPE_REDIRECT,
            $params,
            $actionController
        );
    }

    public static function createSaleLink(
        string $actionName,
        string $actionTitle,
        ?string $actionIcon = null,
        ?array $params = null
    ): self
    {
        return new self(
            $actionName,
            $actionTitle,
            $actionIcon ?? self::DEFAULT_ICON,
            self::ACTION_TYPE_LINK,
            $params
        );
    }

    public function __toString(): string
    {
        return json_encode($this->toArray());
    }

    public function toArray(): array
    {
        return [
            'actionName' => $this->actionName,
            'actionTitle' => $this->actionTitle,
            'actionType' => $this->actionType,
            'actionIcon' => $this->actionIcon,
            'actionController' => $this->actionController ?? '',
            'params' => json_encode($this->params),
        ];
    }
}
