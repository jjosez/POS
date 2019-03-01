<?php
namespace FacturaScripts\Plugins\POS\Model;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\Base;

class ArqueoPOS extends Base\ModelClass
{
    use Base\ModelTrait;

    public $abierto;
    public $fechainicio;
    public $fechafin;
    public $horainicio;
    public $horafin;
    public $idarqueo;
    public $idterminal;
    public $nickusuario;    
    public $saldofinal;
    public $saldoinicial; 
    public $saldomovimientos;
    public $saldoretirado;

    public function clear()
    {
        parent::clear();

        $this->abierto = false;
        $this->fechainicio = date('d-m-Y');
        $this->horainicio = date('H:i:s');
        $this->saldofinal = 0.0;
    }  

    public function isOpened($search, $value)
    {
        switch ($search) {
            case 'terminal':
                $where = [
                  new DataBaseWhere('idterminal', $value, '='),
                  new DataBaseWhere('abierto', true, '=')
                ];
                break;

            case 'user':
                $where = [
                  new DataBaseWhere('nickusuario', $value, '='),
                  new DataBaseWhere('abierto', true, '=')
                ];
                break;
            
            default:
                # code...
                break;
        }
        //echo DataBaseWhere::getSQLWhere($where);

        $data = $this->all($where);
        if ($data) {
            return $data[0];            
        }

        return false;        
    }

    public static function primaryColumn()
    {
        return 'idarqueo';
    }

    public static function tableName()
    {
        return 'arqueospos';
    }
}