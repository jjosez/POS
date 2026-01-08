<?php

namespace FacturaScripts\Plugins\POS\Lib\Services;

use FacturaScripts\Core\Model\Agente;

class Agents
{
     /**
     * @var Agente
     */
    protected $agent;

    public function __construct()
    {
        $this->agent = new Agente();
    }

    /**
     * @return Agente
     */
    public function getAgent(?string $code = null): Agente
    {
        if (!is_null($code)) {
            $this->agent->loadWhereEq('codagente', $code);
        }
        return $this->agent;
    }


    /**
     * Returns the list of available agents for the POS.
     */
    public function getAgentsList(): array
    {
        $agents = [];
        foreach (Agente::all() as $agente) {
            if (!$agente->debaja) {
                $agents[] = [
                    'codagente' => $agente->codagente,
                    'nombre' => $agente->nombre
                ];
            }
        }
        return $agents;
    }


    /**
     * @param string $text
     * @return array
     */
    public function search(string $text): array
    {
        return [];
    }
}
