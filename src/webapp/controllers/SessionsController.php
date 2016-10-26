<?php

namespace tdt4237\webapp\controllers;

use tdt4237\webapp\repository\UserRepository;
use tdt4237\webapp\repository\RequestRepository;
use tdt4237\webapp\models\Request;

class SessionsController extends Controller
{

    public function __construct()
    {
        parent::__construct();
    }

    public function newSession()
    {
        if ($this->auth->check()) {
            $username = $this->auth->user()->getUsername();
            $this->app->flash('info', 'You are already logged in as ' . $username);
            $this->app->redirect('/');
            return;
        }

        $this->render('sessions/new.twig', []);
    }

    public function create()
    {
        $request = $this->app->request;
        $user    = $request->post('user');
        $pass    = $request->post('pass');

        $min_time = $this->app->requestTimeWindow;
        $max_requests = $this->app->maxNumberOfRequestsWithinWindow;

        $request = new Request($_SERVER['REMOTE_ADDR'], time());
        $this->requestRepository->save($request);

        $requestsWithinMinTime = $this->requestRepository->countAfter(time() - $min_time, $_SERVER['REMOTE_ADDR'])[0];
        if($requestsWithinMinTime > $max_requests){
            $this->app->flash('info', sprintf('Too many log in attempts wait %s seconds before trying again.', $min_time));
            $this->app->redirect('/login');
            return;
        }

        if ($this->auth->checkCredentials($user, $pass)) {
            $_SESSION['user'] = $user;
            $isAdmin = $this->auth->user()->isAdmin();
            $_SESSION['isAdmin'] = $isAdmin;

            $this->app->flash('info', "You are now successfully logged in as $user.");
            $this->app->redirect('/');
            return;
        }

        $this->app->flashNow('error', 'Incorrect user/pass combination.');
        $this->render('sessions/new.twig', []);
    }

    public function destroy()
    {
        $this->auth->logout();
        $this->app->redirect('/');
    }
}
