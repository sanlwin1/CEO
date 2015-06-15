<?php
/**
 * Created by PhpStorm.
 * User: july
 * Date: 5/3/2015
 * Time: 9:32 PM
 */

namespace CustomerRelation\Controller;

use CustomerRelation\DataAccess\ContactDataAccess;
use CustomerRelation\DataAccess\CompanyDataAccess;
use CustomerRelation\Entity\Contact;
use CustomerRelation\Helper\ContactHelper;
use Zend\Config\Reader\Json;
use Zend\Crypt\Password\Apache;
use Zend\File\Transfer\Adapter\Http;
use Zend\Filter\File\RenameUpload;
use Zend\Form\Annotation\AnnotationBuilder;
use Zend\Form\Element\Image;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\Validator\Db\NoRecordExists;
use Zend\Validator\File\FilesSize;
use Zend\Validator\File\ImageSize;
use Zend\Validator\File\IsImage;
use Zend\Validator\File\Size;
use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;


class ContactController extends AbstractActionController{

    private function contactTable()
    {
        $adapter=$this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        return new ContactDataAccess($adapter);
    }

    private function companyCombos()
    {
        $adapter=$this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        $dataAccess=new CompanyDataAccess($adapter);
        return $dataAccess->getComboData('companyId', 'name');
    }

    public function indexAction()
    {
        $page=(int)$this->params()->fromQuery('page',1);
        $sort=$this->params()->fromQuery('sort','contactName');
        $sortBy=$this->params()->fromQuery('by', 'asc');
        $filter=$this->params()->fromQuery('filter', '');

        $paginator=$this->contactTable()->fetchAll(true, $filter, $sort, $sortBy);

        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage(10);

        return new ViewModel(array(
            'paginator'=>$paginator,
            'page'=>$page,
            'sort'=>$sort,
            'sortBy'=>$sortBy,
            'filter'=>$filter,
        ));
    }

    public function detailAction()
    {
        $id=(int)$this->params()->fromRoute('id',0);
        $helper=new ContactHelper($this->companyCombos(), $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
        $form=$helper->getForm();
        $contact=$this->contactTable()->getContact($id);
        $isEdit=true;

        if(!$contact){
            $isEdit=false;
            $contact=new Contact();
        }
        $form->bind($contact);
        $request=$this->getRequest();

        if($request->isPost()){
            $post_data=array_merge_recursive($request->getPost()->toArray(),
                $request->getFiles()->toArray());
            $form->setData($post_data);
            $form->setInputFilter($helper->getInputFilter(($isEdit ? $post_data['contactId'] :0), $post_data['name']));
           if($form->isValid()){
               $this->contactTable()->saveContact($contact);
               $this->flashMessenger()->addSuccessMessage('Save Successful');
               return $this->redirect()->toRoute('cr_contact');
           }
        }
        return new ViewModel(array('form'=>$form,
            'id'=>$id, 'isEdit'=>$isEdit));
    }

    public function deleteAction()
    {
        $id=(int)$this->params()->fromRoute('id', 0);

        $contact=$this->contactTable()->getContact($id);
        if($contact){
            $this->contactTable()->deleteContact($id);
            $this->flashMessenger()->addInfoMessage('Delete Successful!');
        }
        return $this->redirect()->toRoute("cr_contact");
    }

    public function exportAction()
    {
        $response=$this->getResponse();

        $excelObj=new \PHPExcel();
        $excelObj->setActiveSheetIndex(0);

        $sheet=$excelObj->getActiveSheet();

        $data=$this->contactTable()->fetchAll(false);
        $columns=array();

        $excelColumn="A";
        $start=2;
        foreach($data as $row)
        {
            if(count($columns)==0){
                $columns=array_keys($row->getArrayCopy());
            }
            foreach($columns as $col){
                $cellId=$excelColumn.$start;
                $sheet->setCellValue($cellId, $row->$col);
                $excelColumn++;
            }
            $start++;
            $excelColumn="A";
        }
        foreach($columns as $col)
        {
            $cellId=$excelColumn.'1';
            $sheet->setCellValue($cellId, $col);
            $excelColumn++;
        }

        $excelWriter=\PHPExcel_IOFactory::createWriter($excelObj, 'Excel2007');
        ob_start();
        $excelWriter->save('php://output');
        $excelOutput=ob_get_clean();

        $filename='attachment; filename="Contact-'. date('Ymdhms').'xlsx"';

        $headers=$response->getHeaders();
        $headers->addHeaderLine('Content-Type','application/ms-excel; charset=UTF-8');
        $headers->addHeaderLine('Content-Disposition', $filename);
        $response->setContent($excelOutput);

        return $response;
    }

    public function jsonDeleteAction()
    {
        $data=$this->params()->fromPost('chkId', array());
        $message="success";

        $db=$this->contactTable()->getAdapter();
        $conn=$db->getDriver()->getConnection();

        try{
            $conn->beginTransaction();
            foreach($data as $id){
                $this->contactTable()->deleteContact($id);
            }
            $conn->commit();
            $this->flashMessenger()->addInfoMessage('Delete successful!');
        }catch (\Exception $ex){
            $conn->rollback();
            $message=$ex->getMessage();
        }
        return new JsonModel(array("message"=>$message));
    }
}