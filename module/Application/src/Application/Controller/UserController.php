<?php

namespace Application\Controller;

use Application\DataAccess\ConstantDataAccess;
use Application\DataAccess\RoleDataAccess;
use Application\DataAccess\UserDataAccess;
use Application\DataAccess\UserRoleDataAccess;
use Application\Entity\User;
use Application\Helper\PasswordHelper;
use Application\Helper\UserHelper;
use Core\Model\ApiModel;
use Core\SundewController;
use Core\SundewExporting;
use Zend\View\Model\ViewModel;

class UserController extends SundewController
{
    /**
     * @return UserDataAccess
     */
    private function userTable()
    {
        return new UserDataAccess($this->getDbAdapter(), $this->getAuthUser()->userId);
    }

    /**
     * @return UserRoleDataAccess
     */
    private function userRoleTable()
    {
        return new UserRoleDataAccess($this->getDbAdapter());
    }

    /**
     * @param $value
     * @return string
     */
    private function encryptPassword($value)
    {
        return md5($value);
    }

    /**
     * @return array
     */
    private function statusCombo()
    {
        $dataAccess = new ConstantDataAccess($this->getDbAdapter(), $this->getAuthUser()->userId);
        return $dataAccess->getComboByName('default_status');
    }

    /**
     * @return array
     */
    private function roleTreeview()
    {
        $dataAccess = new RoleDataAccess($this->getDbAdapter(), $this->getAuthUser()->userId);
        return $dataAccess->getChildren();
    }

    /**
     * @return ViewModel
     */
    public function indexAction()
    {
        $page = (int) $this->params()->fromQuery('page', 1);
        $sort = $this->params()->fromQuery('sort', 'userName');
        $sortBy = $this->params()->fromQuery('by', 'asc');
        $filter = $this->params()->fromQuery('filter', '');
        $pageSize = (int)$this->params()->fromQuery('size', $this->getPageSize());
        $this->setPageSize($pageSize);

        $paginator = $this->userTable()->fetchAll(true,$filter, $sort, $sortBy);

        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage($pageSize);

        return new ViewModel(array(
            'paginator' => $paginator,
            'sort' => $sort,
            'sortBy' => $sortBy,
            'filter' => $filter,
        ));
    }

    /**
     * @return \Zend\Http\Response|ViewModel
     */
    public function detailAction()
    {
        $id = (int)$this->params()->fromRoute('id', 0);
        $action = $this->params()->fromQuery('action', '');
        $helper = new UserHelper($this->getDbAdapter());
        $form = $helper->getForm($this->statusCombo());
        $user = $this->userTable()->getUser($id);
        $isEdit = true;
        $hasImage = 'false';
        $currentImage = "";

        if(!$user){
            $isEdit = false;
            $user = new User();
        }else{
            $hasImage = is_null($user->getImage()) ? 'false' : 'true';
            $currentImage = $user->getImage();
        }

        $userRoles = $this->userRoleTable()->grantRoles($id);

        if($action == 'clone'){
            $isEdit = false;
            $id = 0;
            $user->setUserId(0);
        }

        $form->bind($user);
        $request = $this->getRequest();

        if($request->isPost()){
            $post_data = array_merge_recursive($request->getPost()->toArray(),
            $request->getFiles()->toArray());
            if($isEdit){
                $post_data['password'] = $user->getPassword();
                $post_data['confirmPassword'] = $user->getPassword();
            }else{
                $post_data['password'] = $this->encryptPassword($post_data['password']);
                $post_data['confirmPassword'] = $this->encryptPassword($post_data['confirmPassword']);
            }

            $form->setData($post_data);
            $form->setInputFilter($helper->getInputFilter(($isEdit ? $post_data['userId'] : 0), $post_data['userName']));
            if($form->isValid()){
                $db = $this->userTable()->getAdapter();
                $conn = $db->getDriver()->getConnection();
                try{
                    $image = $user->getImage();
                    if($post_data['hasImage'] ==  'false' && empty($image['name'])){
                        $user->setImage(null);
                    }else if($post_data['hasImage'] == 'true' && empty($image['name']) && $isEdit){
                        $user->setImage($currentImage);
                    }

                    $conn->beginTransaction();
                    $userId = $this->userTable()->saveUser($user)->getUserId();
                    $grant_roles = isset($post_data['grant_roles']) ? $post_data['grant_roles'] : array();
                    $this->userRoleTable()->saveRoles($userId, $grant_roles);
                    $conn->commit();
                    $this->flashMessenger()->addSuccessMessage('Save successful');
                }catch(\Exception $ex){
                    $conn->rollback();
                    $this->flashMessenger()->addErrorMessage($ex->getMessage());
                }
                return $this->redirect()->toRoute('user');
            }
        }

        return new ViewModel(array(
            'form' => $form,
            'id' => $id,
            'isEdit' => $isEdit,
            'hasImage' => $hasImage,
            'roles' => $this->roleTreeview(),
            'userRoles' => $userRoles,
        ));
    }

    /**
     * @return \Zend\Http\Response
     */
    public function deleteAction()
    {

        $id = (int)$this->params()->fromRoute('id', 0);

        $user = $this->userTable()->getUser($id);
        if($user){
            $this->userTable()->deleteUser($id);
            $this->flashMessenger()->addInfoMessage('Delete successful!');
        }

        return $this->redirect()->toRoute("user");
    }

    /**
     * @return \Zend\Stdlib\ResponseInterface
     */
    public function imageAction()
    {
        $id = (int)$this->params()->fromRoute('id', 0);
        $user = $this->userTable()->getUser($id);

        $image_data = null;
        $img_type = 'image/png';

        $response = $this->getResponse();
        $headers = $response->getHeaders();

        if($user && file_exists($user->getImage())){
            $image_data = file_get_contents($user->getImage());
            $img_type = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $user->getImage());
        }else{
            $image_data = file_get_contents('./data/resources/void.png');
        }

        $response->setContent($image_data);
        $headers->addHeaderLine('Content-Type', $img_type);

        return $response;
    }

    /**
     * @return \Zend\Stdlib\ResponseInterface
     */
    public function exportAction()
    {
        $export = new SundewExporting($this->userTable()->fetchAll());
        $filename = 'attachment; filename="User-' . date('YmdHis') . '.xlsx"';
        $excel = $export->getExcel();

        $response = $this->getResponse();
        $headers = $response->getHeaders();
        $headers->addHeaderLine('Content-Type', 'application/ms-excel; charset=UTF-8');
        $headers->addHeaderLine('Content-Disposition', $filename);
        $response->setContent($excel);

        return $response;
    }

    /**
     * @return ApiModel
     */
    public function apiDeleteAction()
    {
        $data = $this->params()->fromPost('chkId', array());
        $db = $this->userTable()->getAdapter();
        $conn = $db->getDriver()->getConnection();
        $api = new ApiModel();
        try{
            $conn->beginTransaction();
            foreach($data as $id){
                $this->userTable()->deleteUser($id);
            }
            $conn->commit();
            $this->flashMessenger()->addInfoMessage('Delete successful!');
        }catch(\Exception $ex){
            $conn->rollback();
            $api->setStatusCode(500);
            $api->setStatusMessage($ex->getMessage());
        }

        return $api;
    }

    /**
     * @return ViewModel
     */
    public function profileAction()
    {
        $user = $this->userTable()->getUser($this->getAuthUser()->userId);
        $current_image = $user->getImage();
        $message = '';

        $hasImage = is_null($user->getImage()) ? 'false' : 'true';

        $helper = new UserHelper($this->userTable()->adapter);
        $form = $helper->getForm($this->statusCombo());
        $form->bind($user);

        $request = $this->getRequest();
        if(!is_null($user) && $request->isPost())
        {
            $post_data = array_merge_recursive(
                $request->getPost()->toArray(),
                $request->getFiles()->toArray());
            $post_data['userId'] = $user->getUserId();
            $post_data['userName'] = $user->getUserName();
            $post_data['password'] = $user->getPassword();
            $post_data['confirmPassword'] = $user->getPassword();

            $form->setData($post_data);
            $form->setInputFilter($helper->getInputFilter($user->getUserId(), $user->getUserName()));
            if($form->isValid()){
                $image = $user->getImage();
                if($post_data['hasImage'] == 'false' && empty($image['name'])){
                    $user->setImage(null);
                }
                else if($post_data['hasImage'] == 'true' && empty($user->getImage['name'])){
                    $user->setImage($current_image);
                }

                $this->userTable()->saveUser($user);
                $message = "Save successful.";
            }

            $form->setData($post_data);
        }

        return new ViewModel(array('form' => $form, 'message' => $message, 'hasImage' => $hasImage));
    }

    /**
     * @return ViewModel
     */
    public function passwordAction()
    {
        $message = "";
        $request = $this->getRequest();

        $helper = new PasswordHelper();
        $form = $helper->getForm();

        if($request->isPost())
        {
            $form->setData($request->getPost());
            $form->setInputFilter($helper->getInputFilter());
            if($form->isValid()){
                $user = $this->userTable()->getUser($this->getAuthUser()->userId);
                $current = $form->get('currentPassword', '');
                $new = $form->get('password', '');
                if($this->encryptPassword($current->getValue()) != $user->getPassword()) {
                    $message = 'Wrong current password.';
                }else{
                    $user->setPassword($this->encryptPassword($new->getValue()));
                    $this->userTable()->saveUser($user);
                    $message = 'Save successful.';
                }
                $current->setValue('');
            }
        }

        return new ViewModel(array('message' => $message, 'form' => $form));
    }
}

