<?php
/**
 * Created by PhpStorm.
 * User: july
 * Date: 3/5/2015
 * Time: 12:28 AM
 */

namespace Application\DataAccess;
use Application\Entity\RoutePermission;
use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\HydratingResultSet;
use Zend\Db\Sql\Select;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\stdlib\Hydrator\ClassMethods;

class RoutePermissionDataAccess extends AbstractTableGateway
{

    public function __construct(Adapter $dbAdapter)
    {
        $this->table = "tbl_route_permission";
        $this->adapter = $dbAdapter;
        $this->initialize();
    }

    public function grantRoles($routeId)
    {
        return $this->select(array('routeId' => $routeId));
    }

    public function saveRoutePermission($routeId, array $roles)
    {
        $this->delete(array('routeId' => $routeId));
        foreach($roles as $roleId)
        {
            $data = array(
                    'routeId' => $routeId,
                    'roleId' => $roleId,
                );
            $this->insert($data);
        }
    }

    public function deleteRoles($routeId)
    {
        $this->delete(array('routeId' => $routeId));
    }
}