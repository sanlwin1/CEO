<?php

namespace Application\Controller;


use Application\DataAccess\ConstantDataAccess;
use Application\Entity\Constant;
use Application\Helper\ConstantHelper;
use Application\Service\SundewExporting;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;

class ConstantController extends AbstractActionController
{
    private function constantTable()
    {
        $adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        return new ConstantDataAccess($adapter);
    }

    public function indexAction()
    {
        $page = (int) $this->params()->fromQuery('page', 1);
        $sort = $this->params()->fromQuery('sort', 'group_code');
        $sortBy = $this->params()->fromQuery('by', 'asc');
        $filter= $this->params()->fromQuery('filter','');
        $pageSize = (int)$this->params()->fromQuery('size', 10);

        $paginator = $this->constantTable()->fetchAll(true,$filter, $sort, $sortBy);
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage($pageSize);

        return new ViewModel(array(
            'paginator' => $paginator,
            'sort' => $sort,
            'sortBy' => $sortBy,
            'filter'=>$filter,
        ));
    }

    public function detailAction()
    {
        $id = (int)$this->params()->fromRoute('id', 0);

        $helper = new ConstantHelper($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
        $form = $helper->getForm();
        $constant = $this->constantTable()->getConstant($id);
        $form->setAttribute("class", "form-horizontal");

        $isEdit = true;
        if(!$constant){
            $isEdit = false;
            $constant = new Constant();
        }

        $form->bind($constant);
        $request = $this->getRequest();

        if($request->isPost()){
            $form->setInputFilter($helper->getInputFilter($id));
            $post_data = $request->getPost()->toArray();
            $form->setData($post_data);
            if($form->isValid()){
                $this->constantTable()->saveConstant($constant);
                $this->flashMessenger()->addSuccessMessage('Save successful');
                return $this->redirect()->toRoute('constant');
            }
        }

        return new ViewModel(array('form' => $form,
            'id' => $id, 'isEdit' => $isEdit));
    }

    public function deleteAction()
    {
        $id = (int)$this->params()->fromRoute('id', 0);
        $constant = $this->constantTable()->getConstant($id);
        if($constant){
            $this->constantTable()->deleteConstant($id);
            $this->flashMessenger()->addInfoMessage('Delete successful!');
        }

        return $this->redirect()->toRoute("constant");
    }

    public function exportAction()
    {
        $export = new SundewExporting($this->constantTable()->fetchAll(false));
        $response = $this->getResponse();
        $filename = 'attachment; filename="Constant-' . date('Ymdhis') . '.xlsx"';
        $headers = $response->getHeaders();
        $headers->addHeaderLine('Content-Type', 'application/ms-excel; charset=UTF-8');
        $headers->addHeaderLine('Content-Disposition', $filename);
        $response->setContent($export->getExcel());

        return $response;
    }

    public function jsonDeleteAction()
    {
        $data = $this->params()->fromPost('chkId', array());
        $db = $this->constantTable()->getAdapter();
        $conn = $db->getDriver()->getConnection();
        $message = 'success';
        try{
            $conn->beginTransaction();
            foreach($data as $id){
                $this->constantTable()->deleteConstant($id);
            }
            $conn->commit();
            $this->flashMessenger()->addInfoMessage('Delete successful!');
        }catch(\Exception $ex){
            $conn->rollback();
            $message = $ex->getMessage();
        }
        return new JsonModel(array("message" => $message));
    }
}

