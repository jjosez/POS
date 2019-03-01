<?php
namespace FacturaScripts\Plugins\POS\Model;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\Base;

class TerminalPOS extends Base\ModelClass
{
    use Base\ModelTrait;

    public $anchopapel;
    public $aceptapagos;
    public $codalmacen;
    public $codcliente;
    public $codserie;    
    public $comandoapertura;
    public $comandocorte;
    public $disponible;     
    public $idterminal;   
    public $nombre; 
    public $numerotickets;  

    public function clear()
    {
        parent::clear();

        $this->disponible = true;
        $this->aceptapagos = true;
        $this->numerotickets = 0;
    } 

    public static function primaryColumn()
    {
        return 'idterminal';
    }

    public static function tableName()
    {
        return 'terminalespos';
    }

    public function allAvailable()
    {
        $where = [
          new DataBaseWhere('disponible', true, '=')
        ];

        //echo DataBaseWhere::getSQLWhere($where);

        return $this->all($where);
    }
}