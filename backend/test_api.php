<?php
// backend/test_api.php

require '../config.php';

// Insere dados de teste no banco de dados
function insert_test_data($pdo) {
    $projects = [
        [
            'title' => 'Projeto de E-commerce com Next.js',
            'description' => 'Desenvolvimento de uma loja virtual de alta performance usando Next.js, Tailwind CSS e integração com Stripe.',
            'image_url' => '/images/ecommerce.jpg',
            'link' => 'https://ecommerce.asventura.com.br'
        ],
        [
            'title' => 'Sistema de Gestão em PHP e MySQL',
            'description' => 'Criação de um sistema de gerenciamento interno para pequenas empresas, com controle de estoque e finanças.',
            'image_url' => '/images/erp.jpg',
            'link' => 'https://erp.asventura.com.br'
        ],
        [
            'title' => 'Landing Page Otimizada para SEO',
            'description' => 'Desenvolvimento de uma landing page estática com foco em conversão e otimização para motores de busca.',
            'image_url' => '/images/landing.jpg',
            'link' => 'https://lp.asventura.com.br'
        ]
    ];

    $stmt = $pdo->prepare('INSERT INTO projects (title, description, image_url, link) VALUES (?, ?, ?, ?)');
    foreach ($projects as $project) {
        $stmt->execute([$project['title'], $project['description'], $project['image_url'], $project['link']]);
    }

    echo "Dados de teste inseridos com sucesso.\n";
}

// Executa a inserção de dados
insert_test_data($pdo);

// Testa a API de Projetos
echo "\n--- Teste da API de Projetos ---\n";
$projects_api_url = 'http://localhost/api/projects.php'; // URL fictícia para teste
$projects_api_path = __DIR__ . '/api/projects.php';

// Simula a execução do arquivo da API
ob_start();
$_SERVER['REQUEST_METHOD'] = 'GET';
require $projects_api_path;
$output = ob_get_clean();

echo "Saída da API:\n";
echo $output . "\n";

?>
