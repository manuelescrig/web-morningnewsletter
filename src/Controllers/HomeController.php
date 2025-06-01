<?php

class HomeController extends Controller {
    public function index() {
        $this->render('home', [
            'title' => 'Welcome to ' . $this->config['app']['name']
        ]);
    }
} 