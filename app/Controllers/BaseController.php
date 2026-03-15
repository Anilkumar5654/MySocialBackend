<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

// Helpers Import
use App\Helpers\InteractionHelper;
use App\Helpers\NotificationHelper; 
use App\Helpers\IdGeneratorHelper; // ✅ Naya Helper Import Kiya

abstract class BaseController extends Controller
{
    protected $request;

    // Helper Properties (Class-based helpers)
    protected $interaction;
    protected $notification; 
    protected $id_generator; // ✅ Property Add Ki

    /**
     * An array of helpers to be loaded automatically.
     */
    protected $helpers = [
        'permission_helper', 
        'url', 
        'form', 
        'session', 
        'format', 
        'text',    
        'number',  
        'media',   
        'time',         
        'admin_logger',
        'currency',
        'trust_score_helper'
    ];

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        
        // 1. Database Connection Global
        $this->db = \Config\Database::connect();

        // 2. Load Engines Globally
        $this->interaction = new InteractionHelper();
        $this->notification = new NotificationHelper(); 
        $this->id_generator = new IdGeneratorHelper(); // ✅ Globally Initialize Kar Diya
    }
}

