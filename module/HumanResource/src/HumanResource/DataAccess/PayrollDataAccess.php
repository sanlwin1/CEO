<?php
/**
 * Created by PhpStorm.
 * User: linn
 * Date: 3/6/2015
 * Time: 9:12 AM
 */

namespace HumanResource\DataAccess;

use Core\SundewTableGateway;
use HumanResource\Entity\Payroll;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Select;

/**
 * Class PayrollDataAccess
 * @package HumanResource\DataAccess
 */
class PayrollDataAccess extends SundewTableGateway{

    protected $view = 'vw_hr_payroll';
    /**
     * @param Adapter $dpAdapter
     * @param Int $userId
     */
    public function __construct(Adapter $dpAdapter, $userId)
    {
        $this->table="tbl_hr_payroll";
        $this->adapter=$dpAdapter;
        $this->initialize();

        $this->useSoftDelete = true;
        parent::__construct($userId);
    }

    /**
     * @param bool $paginated
     * @param string $filter
     * @param string $orderBy
     * @param string $order
     * @return \Zend\Db\ResultSet\ResultSet|\Zend\Paginator\Paginator
     * @throws \Exception
     */
    public function fetchAll($paginated = false, $filter='', $orderBy='fromDate', $order='DESC'){
        if($paginated)
        {
            return $this->paginate($filter, $orderBy, $order, $this->view);
        }

        $select = new Select($this->view);
        return $this->selectOther($select);
    }

    /**
     * @param $id
     * @return array|\ArrayObject|null
     */
    public function getPayroll($id){
        $select = new Select($this->view);
        $select->where(array('payrollId' => $id));
        return $this->selectOther($select)->current();
    }

    /**
     * @param $fromDate
     * @param $toDate
     * @param $staffId
     * @return array|\ArrayObject|null
     */
    public function getPayrollByDate($fromDate, $toDate, $staffId){
        $select = new Select($this->view);
        $select->where(array(
            'staffId' => $staffId,
            'fromDate' => $fromDate,
            'toDate' => $toDate
        ));
        return $this->selectOther($select)->current();
    }

    /**
     * @param $fromDate
     * @param $toDate
     * @param $staffId
     * @return array|\ArrayObject|null
     */
    public function checkPayroll($fromDate, $toDate, $staffId){
        $result = $this->select(array(
            'staffId' => $staffId,
            'fromDate' => $fromDate,
            'toDate' => $toDate
        ));

        return $result->current();
    }

    /**
     * @param array $payroll
     * @return array
     */
    public function savePayroll(array $payroll)
    {
        $id = isset($payroll['payrollId']) ? $payroll['payrollId'] : 0;

        if ($id > 0) {
            $this->update($payroll, Array('payrollId' => $id));
        } else {
            unset($payroll['payrollId']);
            $this->insert($payroll);
        }
        if (!isset($payroll['payrollId'])) {
            $payroll['payrollId'] = $this->getLastInsertValue();
        }
        return $payroll;
    }

    /**
     * @param $id
     */
    public function deletePayroll($id)
    {
        $this->delete(array('payrollId'=>(int)$id));
    }

}