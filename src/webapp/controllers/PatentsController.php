<?php

namespace tdt4237\webapp\controllers;

use tdt4237\webapp\models\Patent;
use tdt4237\webapp\controllers\UserController;
use tdt4237\webapp\validation\PatentValidation;

class PatentsController extends Controller
{

    public function __construct()
    {
        parent::__construct();
    }


    public function index()
    {
        $patent = $this->patentRepository->all();
        if($patent != null)
        {
            $patent->sortByDate();
        }
        $users = $this->userRepository->all();
        $this->render('patents/index.twig', ['patent' => $patent, 'users' => $users]);
    }

    public function search()
    {
        $request = $this->app->request;
        $searchTerm = $request->post('search');
        $patents = $this->patentRepository->search($searchTerm);
        if($patents != null)
        {
            $patents->sortByDate();
        }
        $users = $this->userRepository->all();
        $this->render('patents/index.twig', ['patent' => $patents, 'users' => $users]);
    }

    public function show($patentId)
    {
        $patent = $this->patentRepository->find($patentId);
        if($this->auth->check()) {
            $user = $this->auth->user();
        } else {
            $user = null;
        }
        $request = $this->app->request;
        $message = $request->get('msg');
        $variables = [];

        if($message) {
            $variables['msg'] = $message;

        }

        $this->render('patents/show.twig', [
            'patent' => $patent,
            'user' => $user,
            'flash' => $variables
        ]);

    }

    public function newpatent()
    {

        if ($this->auth->check()) {
            $username = $_SESSION['user'];
            $this->render('patents/new.twig', ['username' => $username]);
        } else {

            $this->app->flash('error', "You need to be logged in to register a patent");
            $this->app->redirect("/");
        }

    }

    public function create()
    {
        if ($this->auth->guest()) {
            $this->app->flash("info", "You must be logged on to register a patent");
            $this->app->redirect("/login");
        } else {
            $request     = $this->app->request;
            $title       = $request->post('title');
            $description = $request->post('description');
            $company     = $request->post('company');
            $date        = date("dmY");

            $validation = new PatentValidation($title, $company);
            if ($validation->isGoodToGo()) {
                $file = $this -> startUpload();
                $patent = new Patent($company, $title, $description, $date, $file);
                $patent->setCompany($company);
                $patent->setTitle($title);
                $patent->setDescription($description);
                $patent->setDate($date);
                if($file) {
                    $patent->setFile($file['storagename']);
                    $patent->setFilename($file['filename']);
                }
                $savedPatent = $this->patentRepository->save($patent);
                $this->app->redirect('/patents/' . $savedPatent . '?msg="Patent succesfully registered');
            }
        }

            $this->app->flashNow('error', join('<br>', $validation->getValidationErrors()));
            $this->app->render('patents/new.twig');
    }

    public function startUpload()
    {
        if(isset($_POST['submit']))
        {
            $target_dir =  "web/uploads/";
            do {
                $target_filename = bin2hex(openssl_random_pseudo_bytes(40));
            } while(file_exists($target_dir . $target_filename));
            $filename = basename($_FILES['uploaded']['name']);
            if(move_uploaded_file($_FILES['uploaded']['tmp_name'], $target_dir . $target_filename))
            {
                return array('filename' => $filename, 'storagename' => $target_filename);
            }
        }
    }

    public function download($patentId)
    {
        if ($this->auth->guest()) {
            $this->app->flash('error', 'You must be logged in to download your files.');
            $this->app->redirect("/login");
            return;
        }

        $patentId = (int)$patentId;
        $patent = $this->patentRepository->find($patentId);

        if(!$patent) {
            $this->app->redirect('/patents');
            return;
        }

        $company = $patent->getCompany();
        $user = $this->auth->user();
        if($user->getUsername() !== $company && $user->getCompany() !== $company) {
            $this->app->flash('error', 'Not authorised to download file for patent.');
            $this->app->redirect('/patents/' . $patent->getPatentId());
            return;
        }

        if(!$patent->getFile()) {
            $this->app->flash('error', 'No download for given patent.');
            $this->app->redirect('/patents/' . $patent->getPatentId());
            return;
        }

        $dl_dir = "web/uploads/";
        $storagename = $patent->getFile();
        $filename = $patent->getFilename();
        $file_target = $dl_dir . $storagename;

        if(!file_exists($file_target)) {
            $this->app->flash('error', 'File missing. Please contact site staff.');
            $this->app->redirect('/patents/' . $patent->getPatentId());
            return;
        }

        $file = fopen($file_target, 'rb');

        header('Content-disposition: attachment; filename="' . $filename . '"');
        header('Content-length: ' . filesize($file_target));
        header('Content-type: application/octet-stream');

        fpassthru($file);
        fclose($file);
    }

    public function destroy($patentId)
    {
    if($this->auth->check() && $this->auth->isAdmin() === true)
    {
        if ($this->patentRepository->deleteByPatentid($patentId))
        {
                $this->app->flash('info', "Sucessfully deleted '$patentId'");
        }
        else
        {
            $this->app->flash('info', "An error ocurred. Unable to delete patent '$patentId'.");
        }
            $this->app->redirect('/admin');
            return;
        } else {
            $this->app->flash('info', "Insufficient privileges to perform action");
            $this->app->redirect('/');
            return;
        }
    }
}
