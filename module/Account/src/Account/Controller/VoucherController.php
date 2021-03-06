<?php
/**
 * Created by PhpStorm.
 * User: NyanTun
 * Date: 3/25/2015
 * Time: 10:51 AM
 */

namespace Account\Controller;

use Account\DataAccess\PayableDataAccess;
use Account\DataAccess\ReceivableDataAccess;
use Account\DataAccess\VoucherDataAccess;
use Account\Entity\Voucher;
use Core\SundewController;
use Core\SundewExporting;
use HumanResource\DataAccess\StaffDataAccess;
use Zend\Http\Request;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

/**
 * Class VoucherController
 * @package Account\Controller
 */
class VoucherController extends SundewController
{
    /**
     * @return VoucherDataAccess
     */
    private function voucherTable()
    {
        return new VoucherDataAccess($this->getDbAdapter());
    }
    private $staffId;

    /**
     * @return ReceivableDataAccess
     */
    private function receivableTable()
    {
        if(!$this->staffId && $this->staffId <= 0){
            $staff = $this->getCurrentStaff();
            $this->staffId = boolval($staff) ? $staff->getStaffId() : 0;
        }

        return new ReceivableDataAccess($this->getDbAdapter(), $this->staffId, $this->getAuthUser()->userId);
    }

    /**
     * @return PayableDataAccess
     */
    private function payableTable()
    {
        if(!$this->staffId && $this->staffId <= 0){
            $staff = $this->getCurrentStaff();
            $this->staffId = boolval($staff) ? $staff->getStaffId() : 0;
        }

        return new PayableDataAccess($this->getDbAdapter(), $this->staffId, $this->getAuthUser()->userId);
    }

    private function prevController($uri)
    {
        $request = new Request();
        $request->setUri($uri);
        $router = $this->serviceLocator->get('Router');
        $routeMatch = $router->match($request);
        if($routeMatch){
            return $routeMatch->getParam('controller');
        }
        return '';
    }

    /**
     * @return ViewModel
     */
    public function indexAction()
    {
        $page = (int)$this->params()->fromQuery('page',1);
        $sort = $this->params()->fromQuery('sort','voucherDate');
        $sortBy = $this->params()->fromQuery('by','desc');
        $filter = $this->params()->fromQuery('filter','');
        $pageSize = (int)$this->params()->fromQuery('size', $this->getPageSize());
        $this->setPageSize($pageSize);

        $paginator=$this->voucherTable()->fetchAll(true,$filter,$sort,$sortBy);
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage($pageSize);

        return new ViewModel(array(
            'paginator'=>$paginator,
            'sort'=>$sort,
            'sortBy'=>$sortBy,
            'filter'=>$filter,
        ));
    }

    /**
     * @return \Zend\Http\Response|ViewModel
     */
    public  function  detailAction()
    {
        $voucherNo = $this->params()->fromRoute('voucher','');
        $voucher = $this->voucherTable()->getVoucher($voucherNo);
        $isApprove = false;

        $referer = $this->getRequest()->getHeader('Referer');
        if($referer){
            $backPath = $this->getRequest()->getHeader('Referer')->getUri();
            if($this->params('controller') == $this->prevController($backPath)){
                $isApprove = true;
            }
        }else{
            $plugin = $this->plugin('url');
            $backPath = $plugin->fromRoute('account_voucher');
        }

        if($voucher == null || $backPath === false){
            $this->flashMessenger()->addWarningMessage('Invalid  voucher.');
            return $this->redirect()->toRoute('account_voucher');
        }

        return new ViewModel(array(
            'voucher'=>$voucher,
            'redirect' => $backPath,
            'isApprove' => $isApprove,
        ));
    }

    /**
     * @return \Zend\Http\Response
     * @throws \Exception
     */
    public function approveAction()
    {
        $voucherNo = $this->params()->fromRoute('voucher','');
        $voucher = $this->voucherTable()->getVoucher($voucherNo);

        if($voucher == null){
            $this->flashMessenger()->addWarningMessage('Invalid  voucher.');
        }else{
            if($voucher->type == 'Receivable'){
                $approveVoucher = $this->receivableTable()->getReceivable($voucher->voucherId, false);
                if($approveVoucher){
                    $approveVoucher->setStatus('A');
                    $this->receivableTable()->saveReceivable($approveVoucher);
                }
            }else{
                $approveVoucher = $this->payableTable()->getPayable($voucher->voucherId, false);
                if($approveVoucher){
                    $approveVoucher->setStatus('A');
                    $this->payableTable()->savePayable($approveVoucher);
                }
            }
            $this->flashMessenger()->addInfoMessage('Approved ' . $voucherNo . ' for ' . $voucher->requester . '.');
        }

        return $this->redirect()->toRoute('account_voucher');
    }

    /**
     * @return \Zend\Http\Response|ViewModel
     * @throws \Exception
     */
    public function cancelAction()
    {
        $voucherNo = $this->params()->fromRoute('voucher','');
        $voucher = $this->voucherTable()->getVoucher($voucherNo);
        $request = $this->getRequest();
        $message = "";

        if($request->isPost()){
            $reason = $this->params()->fromPost('reason', '');
            if(empty($reason)){
                $message = "Voucher can't be canceled without reason.";
            }else{
                if($voucher->type == 'Receivable'){
                    $approveVoucher = $this->receivableTable()->getReceivable($voucher->voucherId, false);
                    if($approveVoucher){
                        $approveVoucher->setReason($reason);
                        $approveVoucher->setStatus('C');
                        $this->receivableTable()->saveReceivable($approveVoucher);
                    }
                }else{
                    $approveVoucher = $this->payableTable()->getPayable($voucher->voucherId, false);
                    if($approveVoucher){
                        $approveVoucher->setReason($reason);
                        $approveVoucher->setStatus('C');
                        $this->payableTable()->savePayable($approveVoucher);
                    }
                }
                $this->flashMessenger()->addInfoMessage('Canceled ' . $voucherNo . ' for ' . $voucher->requester . '.');
                return $this->redirect()->toRoute('account_voucher');
            }
        }

        return new ViewModel(array(
            'voucher'=>$voucher,
            'message'=>$message,
        ));
    }

    /**
     * @return \Zend\Stdlib\ResponseInterface
     */
    public function exportAction()
    {
        $export = new SundewExporting($this->voucherTable()->fetchAll());
        $response = $this->getResponse();
        $filename = 'attachment; filename="Voucher-' . date('YmdHis') . '.xlsx"';

        $headers = $response->getHeaders();
        $headers->addHeaderLine('Content-Type', 'application/ms-excel; charset=UTF-8');
        $headers->addHeaderLine('Content-Disposition', $filename);
        $response->setContent($export->getExcel());

        return $response;
    }
    public function downloadAction()
    {
        $voucherNo = $this->params()->fromRoute('voucher','');
        $voucher = $this->voucherTable()->getVoucher($voucherNo);
        if(!$voucher){
            $this->flashMessenger()->addWarningMessage('Invalid id. ');
            return $this->redirect()->toRoute('account_voucher');
        }
        $file = $voucher->attachmentFile;

        if(!file_exists($file)){
            $this->flashMessenger()->addWarningMessage('Invalid File.');
            return $this->redirect()->toRoute('account_voucher');
        }
        $response = $this->getResponse();
        $headers = $response->getHeaders();
        $headers->addHeaderLine('Content-Type', 'application/octet-stream');
        $headers->addHeaderLine('Content-Length', filesize($file));
        $headers->addHeaderLine('Content-Disposition', 'attachment; filename="' . basename($file) . '"');
        $response->setContent(file_get_contents($file));
        return $response;
    }

}
