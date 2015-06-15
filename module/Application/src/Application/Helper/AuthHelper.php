<?php
/**
 * Created by PhpStorm.
 * User: NyanTun
 * Date: 3/17/2015
 * Time: 1:28 PM
 */

namespace Application\Helper;


use Zend\Form\Element;
use Zend\Form\Form;
use Zend\InputFilter\InputFilter;

class AuthHelper
{
    protected $form;
    public function getForm()
    {
        if(!$this->form)
        {
            $txtUser = new Element\Text('username');
            $txtUser->setLabel('User Name')
                ->setAttribute('class', 'form-control')
                ->setAttribute('placeholder', 'User name');

            $password = new Element\Password('password');
            $password->setLabel('Password')
                ->setAttribute('class', 'form-control')
                ->setAttribute('placeholder', 'Password');

            $remember = new Element\Checkbox('remember');
            $remember->setLabel('Save authentication?')
                ->setAttribute('class', 'form-control');

            $form = new Form();
            $form->setAttribute('class', 'form-horizontal');
            $form->add($txtUser);
            $form->add($password);
            $form->add($remember);

            $this->form = $form;
        }

        return $this->form;
    }
    public function setForm($form)
    {
        $this->form = $form;
    }

    protected $inputFilter;
    public function getInputFilter()
    {
        if(!$this->inputFilter)
        {
            $filter = new InputFilter();
            $filter->add(array(
                'name' => 'username',
                'required' => true,
                'filters' => array(
                    array('name' => 'StripTags'),
                ),
            ));

            $filter->add(array(
                'name' => 'password',
                'required' => true,
                'filters' => array(
                    array('name' => 'StripTags'),
                ),
            ));

            $this->inputFilter = $filter;
        }

        return $this->inputFilter;
    }
    public function setInputFilter($filter)
    {
        $this->inputFilter = $filter;
    }
}