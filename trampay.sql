-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 06/11/2025 às 21:33
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `trampay`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `aceite_termos`
--

CREATE TABLE `aceite_termos` (
  `id` int(11) NOT NULL,
  `tipo_usuario` enum('cliente','profissional') NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `ip_usuario` varchar(45) DEFAULT NULL,
  `data_aceite` timestamp NOT NULL DEFAULT current_timestamp(),
  `versao_termos` varchar(20) DEFAULT '1.0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `agenda_profissional`
--

CREATE TABLE `agenda_profissional` (
  `id` int(11) NOT NULL,
  `profissional_id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `servico_id` int(11) DEFAULT NULL,
  `valor_cobrado` decimal(10,2) DEFAULT NULL,
  `data_servico` date NOT NULL,
  `hora_servico` time NOT NULL,
  `descricao_servico` text DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `justificativa` text DEFAULT NULL,
  `justificado_por` enum('profissional','cliente') DEFAULT NULL,
  `status` enum('agendado','concluido','cancelado') DEFAULT 'agendado',
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `external_ref` varchar(80) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `agenda_profissional`
--

INSERT INTO `agenda_profissional` (`id`, `profissional_id`, `cliente_id`, `servico_id`, `valor_cobrado`, `data_servico`, `hora_servico`, `descricao_servico`, `observacoes`, `justificativa`, `justificado_por`, `status`, `criado_em`, `atualizado_em`, `external_ref`) VALUES
(44, 25, 21, 8, 350.00, '2025-11-13', '14:00:00', '', NULL, NULL, NULL, 'cancelado', '2025-11-06 17:49:33', '2025-11-06 17:56:27', NULL),
(45, 25, 21, 8, 350.00, '2025-11-19', '13:00:00', '', NULL, NULL, NULL, 'agendado', '2025-11-06 18:01:40', '2025-11-06 18:01:40', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `avaliacao`
--

CREATE TABLE `avaliacao` (
  `id` int(11) NOT NULL,
  `profissional_id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `nota` tinyint(4) NOT NULL CHECK (`nota` between 1 and 5),
  `comentario` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `cartoes_clientes`
--

CREATE TABLE `cartoes_clientes` (
  `id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `bandeira` varchar(30) DEFAULT NULL,
  `ultimos4` varchar(10) DEFAULT NULL,
  `tipo` varchar(10) DEFAULT NULL,
  `criado_em` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `clientes`
--

CREATE TABLE `clientes` (
  `id_cliente` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `cpf` varchar(14) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `handle` varchar(30) DEFAULT NULL,
  `foto_perfil` varchar(255) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `cidade` varchar(100) DEFAULT NULL,
  `endereco` varchar(255) DEFAULT NULL,
  `senha` varchar(255) NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `clientes`
--

INSERT INTO `clientes` (`id_cliente`, `nome`, `cpf`, `email`, `handle`, `foto_perfil`, `telefone`, `cidade`, `endereco`, `senha`, `criado_em`) VALUES
(21, 'Isabella Bruno', '559.397.528-02', 'isabella@gmail.com', NULL, NULL, '(11) 97218-4456', NULL, NULL, '$2y$10$mqZZuNRot23DR3ir812s/ed.97TT8QJ.rjjMnW9rG4sfR2XXOwXhS', '2025-11-06 17:49:03');

-- --------------------------------------------------------

--
-- Estrutura para tabela `login_tokens`
--

CREATE TABLE `login_tokens` (
  `token` varchar(64) NOT NULL,
  `profissional_id` int(11) NOT NULL,
  `expires` datetime NOT NULL,
  `used` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `login_tokens`
--

INSERT INTO `login_tokens` (`token`, `profissional_id`, `expires`, `used`, `created_at`) VALUES
('1f384a1b6f472e0384291ca2dd3f3a5017bd405ffe2689019d491c46161168d8', 8, '2025-10-24 21:14:13', 1, '2025-10-24 16:12:13'),
('267d939ae0a5dd011a38b0b9d1223b857a93315487a3d030486de1aec44c7328', 8, '2025-10-24 21:16:14', 1, '2025-10-24 16:14:14'),
('8edb13bda9caaba5d0bb16bb51e70ec9a6460db0f9f1dfcb41bbf4ae14d98580', 8, '2025-10-24 21:30:29', 1, '2025-10-24 16:28:29'),
('d50c5a8fe7b0c6f8a6b3a0d1a700bd3629b4e551e112064f92f7e9b3924eae06', 4, '2025-10-26 05:51:22', 1, '2025-10-26 01:49:22');

-- --------------------------------------------------------

--
-- Estrutura para tabela `pagamentos`
--

CREATE TABLE `pagamentos` (
  `id` int(11) NOT NULL,
  `agenda_id` int(11) NOT NULL,
  `servico_id` int(11) DEFAULT NULL,
  `profissional_id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `fee_percent` decimal(5,2) NOT NULL DEFAULT 12.00,
  `fee_valor` decimal(10,2) NOT NULL DEFAULT 0.00,
  `valor_liquido` decimal(10,2) NOT NULL DEFAULT 0.00,
  `metodo` enum('pix','cartao_credito','cartao_debito') NOT NULL DEFAULT 'pix',
  `card_brand` varchar(20) DEFAULT NULL,
  `card_last4` char(4) DEFAULT NULL,
  `pix_chave` varchar(80) NOT NULL,
  `status` enum('pendente','aprovado','falhou','cancelado') NOT NULL DEFAULT 'pendente',
  `txid` varchar(64) DEFAULT NULL,
  `brcode` text DEFAULT NULL,
  `comprovante_url` varchar(255) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `pagamento_status_log`
--

CREATE TABLE `pagamento_status_log` (
  `id` int(11) NOT NULL,
  `pagamento_id` int(11) NOT NULL,
  `status_old` varchar(20) DEFAULT NULL,
  `status_new` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `note` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `perfil_profissional`
--

CREATE TABLE `perfil_profissional` (
  `id` int(11) NOT NULL,
  `profissional_id` int(11) NOT NULL,
  `banner` varchar(255) DEFAULT NULL,
  `foto_perfil` varchar(255) DEFAULT NULL,
  `publicacao_foto` varchar(255) DEFAULT NULL,
  `comentario` text DEFAULT NULL,
  `data_atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `cep` varchar(9) DEFAULT NULL,
  `bairro` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `perfil_profissional`
--

INSERT INTO `perfil_profissional` (`id`, `profissional_id`, `banner`, `foto_perfil`, `publicacao_foto`, `comentario`, `data_atualizacao`, `cep`, `bairro`) VALUES
(31, 25, NULL, NULL, NULL, 'Desenvolvedor de Sistemas\nTrabalho com Python, JavaScript, HTML, CSS, MySQL e Qt Designer.', '2025-10-31 12:01:53', '09230-650', 'Jardim Utinga'),
(35, 26, NULL, NULL, NULL, 'Mecânico experiente em diagnósticos e revisões.', '2025-10-31 12:11:22', '09220-608', 'Vila Metalúrgica'),
(38, 27, NULL, NULL, NULL, 'Técnico em pequenos reparos residenciais.', '2025-10-31 12:14:47', '09220-240', 'Vila Metalúrgica'),
(40, 28, NULL, NULL, NULL, 'Designer criativo com foco em identidade visual.', '2025-10-31 12:17:05', '09220-240', 'Vila Metalúrgica'),
(43, 29, NULL, NULL, NULL, 'Especialista em cuidados pessoais e estética.', '2025-10-31 12:19:28', NULL, NULL),
(45, 30, NULL, NULL, NULL, 'Desenvolvedor de sistemas e automações.', '2025-10-31 12:22:15', '09220-240', 'Vila Metalúrgica'),
(47, 31, NULL, NULL, NULL, 'Professora dedicada ao ensino prático.', '2025-10-31 12:24:05', '09220-240', 'Vila Metalúrgica'),
(49, 32, NULL, NULL, NULL, 'Profissional de saúde com foco em cuidados domiciliares.', '2025-10-31 12:25:55', '09230-650', 'Jardim Utinga'),
(52, 33, NULL, NULL, NULL, 'Pedreiro e pintor com experiência em reformas.', '2025-10-31 12:28:21', '09220-608', 'Vila Metalúrgica');

-- --------------------------------------------------------

--
-- Estrutura para tabela `portfolio_item`
--

CREATE TABLE `portfolio_item` (
  `id` int(11) NOT NULL,
  `id_profissional` int(11) NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `titulo` varchar(120) DEFAULT NULL,
  `descricao` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `profissional`
--

CREATE TABLE `profissional` (
  `id_profissional` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `cpf` varchar(14) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `cidade` varchar(100) DEFAULT NULL,
  `categoria` varchar(100) DEFAULT NULL,
  `experiencia` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `site` varchar(255) DEFAULT NULL,
  `avatar_url` varchar(255) DEFAULT NULL,
  `senha` varchar(255) NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `profissional`
--

INSERT INTO `profissional` (`id_profissional`, `nome`, `cpf`, `email`, `telefone`, `cidade`, `categoria`, `experiencia`, `bio`, `site`, `avatar_url`, `senha`, `criado_em`) VALUES
(25, 'GUSTAVO GODOI SIBALDELI', '559.397.528-02', 'gustavosibaldeli@gmail.com', '(11) 98886-2604', '', 'Tecnologia', NULL, 'Desenvolvedor de Sistemas\nTrabalho com Python, JavaScript, HTML, CSS, MySQL e Qt Designer.', '', 'uploads/avatar_25_6904a4af105c0.jpeg', '$2y$10$nLp7YAN54j1eOlnUtK99BeX7xQuKrHp8m/2IKpVB2j8/N3JxiDMyi', '2025-10-31 11:50:08'),
(26, 'João Mendes', '123.456.789-00', 'joaomendes.auto@gmail.com', '(11) 98542-3310', '', 'Manutenção automotiva', NULL, 'Mecânico experiente em diagnósticos e revisões.', '', 'uploads/avatar_26_6904a7a176972.jpg', '$2y$10$wUKyhGkn6ZzLknBvwMQrfOz94LAtmiX2n4VBrNR5GKJszvASPiuRe', '2025-10-31 12:07:50'),
(27, 'Carlos Augusto', '234.567.890-11', 'carlosreparos@gmail.com', '(11) 97218-4456', '', 'Reparos domésticos', NULL, 'Técnico em pequenos reparos residenciais.', '', 'uploads/avatar_27_6904a825399dc.jpg', '$2y$10$CwIiuZmI9/h.zCcOAbYo/.sgqcQSS0GbP3RxbCi0lQRV5GXnsRNo.', '2025-10-31 12:14:02'),
(28, 'Marina Lopes', '345.678.901-22', 'marinalopes.design@gmail.com', '(11) 99163-7821', '', 'Design e Criação', NULL, 'Designer criativo com foco em identidade visual.', '', 'uploads/avatar_28_6904a8f7a773d.jpg', '$2y$10$4d/p3mZkzyMB1PYT2vyMYe.e9KdBptiGEBmtSBAbgLOTcCsPpv/lK', '2025-10-31 12:16:28'),
(29, 'Ana Paula', '456.789.012-33', 'anapaulabeleza@gmail.com', '(11) 98427-9965', '', 'Estética e Beleza', NULL, 'Especialista em cuidados pessoais e estética.', '', 'uploads/avatar_29_6904a9435447b.jpg', '$2y$10$QK.3Bf26AIVhdkCCeatHg.lRRF.yh8.XjwgDe0LJSRakiTvlNuimS', '2025-10-31 12:19:09'),
(30, 'Luiz Gustavo', '567.890.123-44', 'gustavos.tech@gmail.com', '(11) 98835-1074', '', 'Tecnologia', NULL, 'Desenvolvedor de sistemas e automações.', '', 'uploads/avatar_30_6904a9da29d5e.jpg', '$2y$10$czb1FU7a36/Di65XXEuwT.geNnwJM92z/ZO2uZktvkGa9wHmwgshG', '2025-10-31 12:21:15'),
(31, 'Juliana Reis', '678.901.234-55', 'julianaprof@gmail.com', '(11) 99742-5561', '', 'Aulas e Educação', NULL, 'Professora dedicada ao ensino prático.', '', 'uploads/avatar_31_6904aa63b39be.jpg', '$2y$10$4/2GCHnxRvEe/9yIs61JCOWE1BCAqmTmPe7txtd.awBcVgQWckkIm', '2025-10-31 12:23:03'),
(32, 'Rafael Moreira', '789.012.345-66', 'dr.rafaelmoreira@gmail.com', '(11) 99654-2032', '', 'Saúde', NULL, 'Profissional de saúde com foco em cuidados domiciliares.', '', 'uploads/avatar_32_6904ab02326c7.jpg', '$2y$10$V8GgEmDRJ6/6GCQl4pdKIe4Zhq6WMLlzkqkXtWua40cHTQ3/nbbRG', '2025-10-31 12:25:15'),
(33, 'Pedro Rocha', '890.123.456-77', 'pedroreformas@gmail.com', '(11) 98112-8790', '', 'Mudanças e Reformas', NULL, 'Pedreiro e pintor com experiência em reformas.', '', 'uploads/avatar_33_6904ab7b79ad5.jpg', '$2y$10$uyK5hTxlWp1ohJggBXY2HeAPRyIEez00Sy3mveNspSI0zRxmKgHc.', '2025-10-31 12:27:42');

-- --------------------------------------------------------

--
-- Estrutura para tabela `profissional_portfolio`
--

CREATE TABLE `profissional_portfolio` (
  `id` int(11) NOT NULL,
  `id_profissional` int(11) NOT NULL,
  `url` varchar(255) NOT NULL,
  `legenda` varchar(140) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `profissional_portfolio`
--

INSERT INTO `profissional_portfolio` (`id`, `id_profissional`, `url`, `legenda`, `criado_em`) VALUES
(13, 25, 'uploads/portfolio_25_99eaa6d0ec92.png', NULL, '2025-10-31 11:59:54');

-- --------------------------------------------------------

--
-- Estrutura para tabela `profissional_servico`
--

CREATE TABLE `profissional_servico` (
  `id` int(11) NOT NULL,
  `id_profissional` int(11) NOT NULL,
  `titulo` varchar(100) NOT NULL,
  `descricao` text DEFAULT NULL,
  `preco_min` decimal(10,2) DEFAULT NULL,
  `prazo_dias` int(11) DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `profissional_servico`
--

INSERT INTO `profissional_servico` (`id`, `id_profissional`, `titulo`, `descricao`, `preco_min`, `prazo_dias`, `ativo`, `criado_em`, `atualizado_em`) VALUES
(8, 25, 'Criação de Sistema de Login e Cadastro', 'Sistema completo com banco de dados, design moderno e segurança. Ideal para empresas e plataformas online.', 350.00, 7, 1, '2025-10-31 11:54:35', '2025-10-31 11:57:40'),
(9, 26, 'Troca de óleo e revisão completa.', 'Verificação de filtros, fluídos, freios e sistema elétrico.', 180.00, 2, 1, '2025-10-31 12:10:58', NULL),
(10, 27, 'Conserto de portas e tomadas.', 'Ajustes, trocas de fechaduras, reparos elétricos simples e vedação.', 120.00, 1, 1, '2025-10-31 12:15:23', NULL),
(11, 28, 'Criação de logo e banner profissional.', 'Entrega de arquivo vetorial, variações de logo e arte para redes.', 250.00, 3, 1, '2025-10-31 12:17:32', NULL),
(12, 29, 'Manicure e design de sobrancelhas.', 'Corte, unha em gel/esmaltada e modelagem de sobrancelhas.', 80.00, 1, 1, '2025-10-31 12:19:52', '2025-10-31 12:20:05'),
(13, 30, 'Sistema de login e cadastro completo.', 'Cadastro seguro, validação, recuperação de senha e integração MySQL.', 350.00, 5, 1, '2025-10-31 12:22:12', NULL),
(14, 31, 'Aulas de informática e matemática.', 'Aulas personalizadas com exercícios práticos e material de apoio.', 60.00, NULL, 1, '2025-10-31 12:23:42', NULL),
(15, 32, 'Consulta e acompanhamento básico.', 'Atendimento domiciliar para curativos, aferição e orientações.', 150.00, 1, 1, '2025-10-31 12:25:50', NULL),
(16, 33, 'Pintura e pequenos reparos residenciais.', 'Preparação de superfícies, pintura, assentamento e pequenos consertos.', 200.00, 3, 1, '2025-10-31 12:28:17', NULL);

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `aceite_termos`
--
ALTER TABLE `aceite_termos`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `agenda_profissional`
--
ALTER TABLE `agenda_profissional`
  ADD PRIMARY KEY (`id`),
  ADD KEY `profissional_id` (`profissional_id`),
  ADD KEY `cliente_id` (`cliente_id`),
  ADD KEY `fk_agenda_servico` (`servico_id`),
  ADD KEY `idx_external_ref` (`external_ref`);

--
-- Índices de tabela `avaliacao`
--
ALTER TABLE `avaliacao`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_avaliacao` (`profissional_id`,`cliente_id`),
  ADD KEY `idx_prof` (`profissional_id`),
  ADD KEY `idx_cli` (`cliente_id`);

--
-- Índices de tabela `cartoes_clientes`
--
ALTER TABLE `cartoes_clientes`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id_cliente`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `handle` (`handle`);

--
-- Índices de tabela `login_tokens`
--
ALTER TABLE `login_tokens`
  ADD PRIMARY KEY (`token`),
  ADD KEY `profissional_id` (`profissional_id`),
  ADD KEY `expires` (`expires`),
  ADD KEY `used` (`used`);

--
-- Índices de tabela `pagamentos`
--
ALTER TABLE `pagamentos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_txid` (`txid`),
  ADD KEY `fk_pag_agenda` (`agenda_id`);

--
-- Índices de tabela `pagamento_status_log`
--
ALTER TABLE `pagamento_status_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pmtlog` (`pagamento_id`);

--
-- Índices de tabela `perfil_profissional`
--
ALTER TABLE `perfil_profissional`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_perfil_prof` (`profissional_id`),
  ADD KEY `profissional_id` (`profissional_id`);

--
-- Índices de tabela `portfolio_item`
--
ALTER TABLE `portfolio_item`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_profissional` (`id_profissional`);

--
-- Índices de tabela `profissional`
--
ALTER TABLE `profissional`
  ADD PRIMARY KEY (`id_profissional`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Índices de tabela `profissional_portfolio`
--
ALTER TABLE `profissional_portfolio`
  ADD PRIMARY KEY (`id`),
  ADD KEY `profissional_id` (`id_profissional`);

--
-- Índices de tabela `profissional_servico`
--
ALTER TABLE `profissional_servico`
  ADD PRIMARY KEY (`id`),
  ADD KEY `profissional_id` (`id_profissional`),
  ADD KEY `ativo` (`ativo`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `aceite_termos`
--
ALTER TABLE `aceite_termos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `agenda_profissional`
--
ALTER TABLE `agenda_profissional`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT de tabela `avaliacao`
--
ALTER TABLE `avaliacao`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de tabela `cartoes_clientes`
--
ALTER TABLE `cartoes_clientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id_cliente` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT de tabela `pagamentos`
--
ALTER TABLE `pagamentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT de tabela `pagamento_status_log`
--
ALTER TABLE `pagamento_status_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de tabela `perfil_profissional`
--
ALTER TABLE `perfil_profissional`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT de tabela `portfolio_item`
--
ALTER TABLE `portfolio_item`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `profissional`
--
ALTER TABLE `profissional`
  MODIFY `id_profissional` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT de tabela `profissional_portfolio`
--
ALTER TABLE `profissional_portfolio`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de tabela `profissional_servico`
--
ALTER TABLE `profissional_servico`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `agenda_profissional`
--
ALTER TABLE `agenda_profissional`
  ADD CONSTRAINT `agenda_profissional_ibfk_1` FOREIGN KEY (`profissional_id`) REFERENCES `profissional` (`id_profissional`) ON DELETE CASCADE,
  ADD CONSTRAINT `agenda_profissional_ibfk_2` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id_cliente`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_agenda_servico` FOREIGN KEY (`servico_id`) REFERENCES `profissional_servico` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `pagamentos`
--
ALTER TABLE `pagamentos`
  ADD CONSTRAINT `fk_pag_agenda` FOREIGN KEY (`agenda_id`) REFERENCES `agenda_profissional` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `pagamento_status_log`
--
ALTER TABLE `pagamento_status_log`
  ADD CONSTRAINT `fk_pmtlog` FOREIGN KEY (`pagamento_id`) REFERENCES `pagamentos` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `perfil_profissional`
--
ALTER TABLE `perfil_profissional`
  ADD CONSTRAINT `perfil_profissional_ibfk_1` FOREIGN KEY (`profissional_id`) REFERENCES `profissional` (`id_profissional`) ON DELETE CASCADE;

--
-- Restrições para tabelas `portfolio_item`
--
ALTER TABLE `portfolio_item`
  ADD CONSTRAINT `portfolio_item_ibfk_1` FOREIGN KEY (`id_profissional`) REFERENCES `profissional` (`id_profissional`) ON DELETE CASCADE;

--
-- Restrições para tabelas `profissional_portfolio`
--
ALTER TABLE `profissional_portfolio`
  ADD CONSTRAINT `profissional_portfolio_ibfk_1` FOREIGN KEY (`id_profissional`) REFERENCES `profissional` (`id_profissional`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `profissional_servico`
--
ALTER TABLE `profissional_servico`
  ADD CONSTRAINT `profissional_servico_ibfk_1` FOREIGN KEY (`id_profissional`) REFERENCES `profissional` (`id_profissional`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
