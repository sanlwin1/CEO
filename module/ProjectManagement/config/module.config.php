<?php
/**
 * Created by PhpStorm.
 * User: NyanTun
 * Date: 5/25/2015
 * Time: 1:02 PM
 */

return array(
    'controllers' => array(
        'invokables' => array(
            'ProjectManagement\Controller\Project' => 'ProjectManagement\Controller\ProjectController',
            'ProjectManagement\Controller\Task' => 'ProjectManagement\Controller\TaskController',
            'ProjectManagement\Controller\Report' => 'ProjectManagement\Controller\ReportController',
        ),
    ),
    'view_manager' => array(
        'template_path_stack' => array(
            __DIR__ . '/../view',
        ),
    ),
);