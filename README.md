# SnapHub Pages

## Visão Geral
O SnapHub Pages é uma plataforma especializada na criação e gerenciamento de páginas de vendas, landing pages e exibição de produtos para o ecossistema SnapHub. A aplicação permite criar, personalizar e publicar rapidamente páginas para:

## Tecnologias Utilizadas
- **Backend**: Laravel 12.x (PHP 8.2+)
- **Frontend Interativo**: Livewire 3.x
- **CSS**: TailwindCSS 4.x
- **Bancos de Dados**: MySQL/PostgreSQL

## Requisitos do Sistema
- PHP 8.2 ou superior
- Composer
- Node.js 16+ e NPM
- MySQL 8.0 ou PostgreSQL 12+

## Instalação

```bash
# Clone o repositório
git clone https://github.com/IsottonTecnologia/snaphubb-pages.git
cd snaphubb-pages

# Instalar dependências PHP
composer install

# Instalar dependências JavaScript
npm install

# Configurar ambiente
cp .env.example .env
php artisan key:generate

# Configurar banco de dados no arquivo .env e executar migrações
php artisan migrate

# Compilar assets
npm run dev

# Iniciar servidor de desenvolvimento
php artisan serve
```
