# 🚀 Trampay — Sistema Web de Marketplace de Serviços

## 📖 Visão Geral

O **Trampay** é uma aplicação web full stack desenvolvida para conectar clientes a profissionais de diversas áreas, funcionando como um marketplace de serviços. A plataforma permite cadastro de usuários, criação de perfis profissionais, busca por serviços, agendamentos e processamento de pagamentos.

Este projeto foi desenvolvido como Trabalho de Conclusão de Curso (TCC), com o objetivo de aplicar, na prática, conceitos de engenharia de software, desenvolvimento web e modelagem de banco de dados em um sistema real.

---

## 🧩 Funcionalidades Principais

### 🔐 Autenticação e Gerenciamento de Usuários

* Cadastro e login de **clientes** e **profissionais**
* Sistema de autenticação com validação de dados
* Recuperação e redefinição de senha

---

### 👨‍🔧 Área do Profissional

* Criação e edição de perfil
* Upload de foto (avatar)
* Cadastro e gerenciamento de serviços oferecidos
* Upload de portfólio
* Visualização de avaliações recebidas

---

### 👥 Área do Cliente

* Cadastro e autenticação
* Busca por profissionais
* Visualização de perfis públicos
* Acesso a serviços e avaliações

---

### 🔎 Sistema de Busca e Descoberta

* Listagem de profissionais por categoria
* Filtros e navegação por serviços
* Visualização detalhada de perfis (serviços, localização e avaliações)

---

### 📅 Sistema de Agendamento

* Seleção de data e horário
* Criação de agendamentos
* Acompanhamento do status do serviço

---

### 💳 Sistema de Pagamentos

* Integração com pagamentos via **Pix** e **cartão**
* Confirmação de pagamentos
* Atualização automática de status via webhook

---

### ⭐ Sistema de Avaliações

* Clientes podem avaliar profissionais
* Exibição pública de avaliações nos perfis

---

## 🏗️ Arquitetura do Sistema

O projeto segue uma estrutura organizada baseada em separação de responsabilidades:

* **Camada de Apresentação:** HTML, CSS e JavaScript
* **Camada de Aplicação:** PHP (regras de negócio e controle)
* **Camada de Dados:** MySQL (armazenamento e consultas)

Essa abordagem facilita manutenção, escalabilidade e organização do código.

---

## 🛠️ Tecnologias Utilizadas

| Camada         | Tecnologia            |
| -------------- | --------------------- |
| Front-end      | HTML, CSS, JavaScript |
| Back-end       | PHP                   |
| Banco de Dados | MySQL                 |
| Ambiente Local | XAMPP                 |
| Pagamentos     | Pix e Cartão          |
| E-mails        | PHPMailer             |

---

## 📂 Estrutura do Projeto

```
/TCC
 ├── /PHPMailer
 ├── /uploads
 ├── arquivos PHP (lógica do sistema)
 ├── arquivos HTML (interfaces)
 ├── banco de dados (.sql)
 └── assets (imagens e recursos visuais)
```

---

## ⚙️ Como Executar o Projeto

### 📋 Pré-requisitos

* XAMPP instalado (Apache e MySQL)

---

### ▶️ Passo a Passo

1. Copiar o projeto para:

```
C:\xampp\htdocs\
```

2. Iniciar os serviços no XAMPP:

* Apache ✔️
* MySQL ✔️

3. Importar o banco de dados:

* Acessar o **phpMyAdmin**
* Importar o arquivo `trampay.sql`

4. Executar no navegador:

```
http://localhost/TCC
```

---

## 🎯 Objetivos do Projeto

* Desenvolver uma aplicação web completa (full stack)
* Aplicar conceitos de engenharia de software
* Implementar funcionalidades reais como autenticação, agendamento e pagamentos
* Simular um sistema de marketplace utilizado no mundo real

---

## 🚀 Melhorias Futuras

* Implementação de API REST
* Melhoria de interface e experiência do usuário (UI/UX)
* Deploy em servidor online
* Sistema de notificações em tempo real
* Otimizações de performance e segurança

---

## 👨‍💻 Autor

**Gustavo Godoi Sibaldeli**
Estudante de Engenharia de Software

---

## 📄 Licença

Este projeto foi desenvolvido para fins educacionais e demonstração de habilidades técnicas.

---

## 📌 Considerações Finais

O **Trampay** representa a construção de um sistema completo e funcional, integrando múltiplas funcionalidades essenciais de uma aplicação real. O projeto demonstra na prática a capacidade de desenvolver, estruturar e implementar soluções web com foco em usabilidade e organização.
