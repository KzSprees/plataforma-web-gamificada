# 🎮 Plataforma Gamificada de Aprendizagem Web

> 🌐 **Acede à aplicação online:** [https://weblearning.page.gd/inicio.php]

Uma plataforma web interativa desenvolvida para auxiliar estudantes no aprendizado de **HTML5, CSS3 e PHP** por meio de perguntas de múltipla escolha e mecânicas de gamificação.

---

### 🚀 Funcionalidades Principais

- **Mecânica Gamificada:** Quizzes de múltipla escolha sobre desenvolvimento web, opção de pular questões, acompanhamento de histórico de progresso e ranking classificatório entre os utilizadores.
- **Autenticação & Gestão de Acesso:** Sistema completo de registo, login, encerramento de sessão e recuperação de acesso.
- **Painel Administrativo:** Gestão restrita de perguntas, respostas e utilizadores para administradores.
- **Segurança Integrada:** Validação de formulários, controlo de sessão ativa e proteção contra ataques CSRF via tokens temporários.

---

### 🛠️ Tecnologias Utilizadas

- **Frontend:** HTML5, CSS3
- **Backend:** PHP
- **Banco de Dados:** MySQL
- **Servidor & Implantação:** Apache, XAMPP (Desenvolvimento) / Servidor Web (Produção)

---

### 🗂️ Estrutura do Código

- **Conexão e Configuração:** `conexao.php`, `config.php`
- **Autenticação e Segurança:** `sessao.php`, `admin_auth.php`, `csrf.php`
- **Lógica do Jogo e Gamificação:** `iniciar.php`, `corrigir.php`, `pular.php`, `desempenho.php`, `ranking.php`
- **Painel Administrativo:** `admin.php`, `admin_login.php`, `editar.php`

---

### 🔧 Como Executar Localmente

1. Clona o repositório:
   ```bash
   git clone [https://github.com/KzSprees/plataforma-web-gamificada.git](https://github.com/KzSprees/plataforma-web-gamificada.git)
