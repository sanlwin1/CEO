<?php
/**
 * Created by PhpStorm.
 * User: july
 * Date: 3/5/2015
 * Time: 11:51 AM
 */

namespace Account\DataAccess;

use Account\Entity\Receivable;
use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\HydratingResultSet;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Db\TableGateway\TableGateway;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Paginator\Paginator;
use Zend\Stdlib\Hydrator\ClassMethods;


class ReceivableDataAccess extends AbstractTableGateway
{
    protected $staffId;

    public function __construct(Adapter $dbAdapter, $staffId)
    {
        $this->staffId = $staffId;
        $this->table = "tbl_account_receivable";
        $this->adapter = $dbAdapter;
        $this->resultSetPrototype = new HydratingResultSet(new ClassMethods(), new Receivable());
        $this->initialize();
    }

    public function getVoucherNo($date)
    {
        $select = new Select($this->table);
        $select->where(array('voucherDate' => $date));
        $select->columns(array(new Expression('max(voucherNo) as MaxVoucherNo')));
        $statement = $this->sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        $maxVoucherNo = $result->current()['MaxVoucherNo'];

        $voucherDate = strtotime($date);
        $number = ($maxVoucherNo == null)? 0 : substr($maxVoucherNo, -4);
        $number = (int)$number + 1;
        $generate = 'RV' . date('Ymd', $voucherDate) . sprintf('%04d', $number);

        return $generate;
    }

    public function fetchAll($paginated=false,$filter='',$orderBy='voucherNo',$order='ASC')
    {
        $view = 'vw_account_receivable';
        if($paginated){
            $select = new Select($view);
            $select->order($orderBy . ' ' . $order);
            $where = new Where();
            $where->literal("concat_ws(' ',description, voucherNo, Type) LIKE ?", '%' . $filter . '%')
                ->and->equalTo('depositBy', $this->staffId);
            $select->where($where);
            $paginatorAdapter = new DbSelect($select, $this->adapter);
            $paginator = new Paginator($paginatorAdapter);
            return $paginator;
        }
        $tableGateway = new TableGateway($view, $this->adapter);
        return $tableGateway->select(array('depositBy' => $this->staffId));
    }

    public function getReceivableView($id)
    {
        $select = new Select('vw_account_receivable');
        $select->where(array('receiveVoucherId' => $id, 'depositBy' => $this->staffId));
        $statement = $this->sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        return $result->current();
    }

    public function getReceivable($id, $withPermission = true)
    {
        $id=(int)$id;
        if($withPermission){
            $rowset = $this->select(array('receiveVoucherId'=>$id, 'depositBy'=> $this->staffId));
        }else{
            $rowset = $this->select(array('receiveVoucherId'=>$id));
        }
        return $rowset->current();
    }

    public function saveReceivable(Receivable $receivable)
    {
        $id = $receivable->getReceiveVoucherId();
        $data = $receivable->getArrayCopy();

        if(empty($data['status'])){
            $data['status']='R';
            if(empty($data['requestedDate'])) {
                $data['requestedDate'] = date('Y-m-d H:i:s', time());
            }
            $data['depositBy'] = $this->staffId;
        }else if($data['status'] === 'A'){
            if(empty($data['approvedDate'])) {
                $data['approvedDate'] = date('Y-m-d H:i:s', time());
            }
            $data['approveBy'] = $this->staffId;
        }else if($data['status'] === 'C'){
            if(empty($data['approvedDate'])) {
                $data['approvedDate'] = date('Y-m-d H:i:s', time());
            }
            $data['approveBy'] = $this->staffId;
        }else{
            throw new \Exception('Invalid Status');
        }

        if($id > 0){
            $this->update($data, array('receiveVoucherId' => $id));
        }else{
            unset($data['receiveVoucherId']);
            $this->insert($data);
        }

        if(!$receivable->getReceiveVoucherId())
        {
            $receivable->setReceiveVoucherId($this->getLastInsertValue());
        }

        return $receivable;
    }
}