<?php
namespace FacturaScripts\Plugins\POS\Model;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\Base;

class POSTerminal extends Base\ModelClass
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
        
        $this->aceptapagos = true;
        $this->anchopapel = 45;
        $this->disponible = true;
        $this->numerotickets = 1;
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
