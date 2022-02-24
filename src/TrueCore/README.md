#### КОТОВЗЛОМ

### Инструкция:

1. Сначала устанавливаем Laravel:
- **composer create-project --prefer-dist laravel/laravel .**
2. Генерируем символическую ссылку в _public/_ на _storage/app/public_:
- **cd <<папка проекта>>/public && ln -s ../storage/app/public storage && cd ..**
3. Для установки TrueCore добавляем в composer.json строку
- **"repositories": [
          {
              "type": "vcs",
              "url": "git@github.com:zcell/TRUE-CORE.git"
          }
      ],**
4. В _composer.json_ в секции _require_ меняем значение ключа **"php"** на **"php": "^7.4",**
5. Выполняем команду **composer require zcell/true-core**
5. Удалите все лишние роуты в папке routes
6. Выполняем команду **php artisan vendor:publish --provider="TrueCore\App\Providers\SeedServiceProvider" --force**
6. Выполняем команду **composer dump-autoload**
7. Выполняем команду **rm -rf database/migrations/&ast;**
8. Правим файл _.env_, указываем все необходимые данные для подключения к БД
9. Выполняем команду **php artisan migrate && php artisan db:seed**
9. Выполняем команду **php artisan jwt:secret**
10. Правим config/auth.php, как в ядре (TODO: решить с мерджингом настроек)
11. Добавляем в Kernel.php в routed middleware


        'jwt.authenticate' => \TrueCore\App\Http\Middleware\JwtAuthenticate::class,
        'jwt.refresh' => \Tymon\JWTAuth\Http\Middleware\RefreshToken::class,


Создание сгенерированных классов (сервисов, контроллеров, ресурсов, request'ов, модели) производится командой **php artisan generate:from_table <<название таблицы>> --namespace="название"** 
