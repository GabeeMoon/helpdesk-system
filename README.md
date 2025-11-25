# 🎫 Sistema de Chamados (Helpdesk System)

Um sistema de Helpdesk moderno, responsivo e seguro desenvolvido em PHP nativo, focado em oferecer uma experiência "premium" com design Glassmorphism e funcionalidades essenciais para gestão de suporte.

![Status](https://img.shields.io/badge/Status-Em_Desenvolvimento-yellow)
![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?logo=php&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?logo=bootstrap&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-green)

## ✨ Funcionalidades Principais

*   **Design Moderno (Glassmorphism):** Interface visualmente rica com efeitos de vidro, gradientes e animações suaves.
*   **Painel Responsivo:** Dashboard adaptável que funciona perfeitamente em desktops, tablets e celulares.
*   **Gestão de Chamados:**
    *   Criação, edição e acompanhamento de tickets.
    *   Atribuição de responsáveis.
    *   Status dinâmicos (Criado, Em Análise, Respondido, Finalizado).
*   **Chat em Tempo Real (Simulado):** Interface de chat estilo "Messenger" para comunicação dentro do chamado, com suporte a avatares.
*   **Anexos:** Suporte para envio e visualização de anexos (imagens, documentos).
*   **Modo Escuro (Dark Mode):** Alternância nativa entre temas claro e escuro, persistente via LocalStorage.
*   **Perfis de Usuário:** Diferentes níveis de acesso (Usuário, Admin, Root) com fotos de perfil personalizáveis.

## 🚀 Tecnologias Utilizadas

*   **Backend:** PHP (Nativo, sem frameworks pesados)
*   **Frontend:** HTML5, CSS3 (Custom Properties + Bootstrap 5), JavaScript (jQuery)
*   **Banco de Dados:** MySQL / MariaDB
*   **Ícones:** FontAwesome 6
*   **Fontes:** Google Fonts (Inter, Outfit)

## 🛠️ Instalação e Configuração

### Pré-requisitos
*   Servidor Web (Apache/Nginx)
*   PHP 7.4 ou superior
*   MySQL 5.7 ou superior

### Passo a Passo

1.  **Clone o repositório:**
    ```bash
    git clone https://github.com/GabeeMoon/helpdesk-system.git
    cd helpdesk-system
    ```

2.  **Configure o Banco de Dados:**
    *   Crie um banco de dados no seu MySQL (ex: `sistema_chamados`).
    *   Importe o arquivo `database.sql` para criar as tabelas necessárias.
    *   *Alternativamente, acesse `setup_db.php` no navegador para configurar automaticamente (requer configuração prévia de conexão).*

3.  **Configure a Conexão:**
    *   Edite o arquivo `configs/db.php` (ou onde a conexão estiver definida, verifique `index.php` ou `classes/`) com suas credenciais do banco de dados:
    ```php
    $servername = "localhost";
    $username = "seu_usuario";
    $password = "sua_senha";
    $dbname = "sistema_chamados";
    ```

4.  **Acesse o Sistema:**
    *   Abra o navegador e vá para `http://localhost/helpdesk-system`.
    *   **Login Padrão (se houver no SQL):**
        *   Email: `admin@admin.com`
        *   Senha: `admin` (Recomenda-se alterar imediatamente).

## 📂 Estrutura de Pastas

```
helpdesk-system/
├── assets/             # CSS, JS, Imagens
├── classes/            # Classes PHP (Ticket.php, User.php)
├── configs/            # Configurações de DB
├── pages/              # Views (Dashboard, Login, Detalhes)
├── uploads/            # Arquivos anexados
├── database.sql        # Schema do Banco de Dados
├── index.php           # Roteador / Entry Point
└── README.md           # Documentação
```

## 🤝 Contribuição

Contribuições são bem-vindas! Sinta-se à vontade para abrir Issues ou enviar Pull Requests.

1.  Faça um Fork do projeto
2.  Crie sua Feature Branch (`git checkout -b feature/MinhaFeature`)
3.  Commit suas mudanças (`git commit -m 'Adiciona MinhaFeature'`)
4.  Push para a Branch (`git push origin feature/MinhaFeature`)
5.  Abra um Pull Request

## 📄 Licença

Este projeto está sob a licença MIT. Veja o arquivo [LICENSE](LICENSE) para mais detalhes.

---
Desenvolvido com 💙 por [GabeeMoon](https://github.com/GabeeMoon)
