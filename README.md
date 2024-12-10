# Обработчик кредитных заявок (тестовое задание для Wiam Group)

Приложение обрабатывает заявки пользователей на кредит, обеспечивая целостность данных и предотвращая условия гонки с помощью механизмов блокировки в базе данных PostgreSQL. Проект предоставляет API из 2 методов: `POST /requests` и `GET /processor?delay=5`. Первый позволяет создавать новые заявки на кредит от пользователей, а второй запускает обработку всех необработанных заявок. Обработка заявок сводится к изменению статуса на Одобрено или Отказано. Решение принимается случайным образом с вероятностью 10% в пользу одобрения. При этом, если у пользователя уже есть хотя бы одна одобренная заявка, то все новые заявки должны получать отказ.

По условию задачи возможен одновременный запуск нескольких процессов обработки заявок, что не должно мешать корректному выполнению и сохранению базовых условий: не более одной одобренной заявки на пользователя. При этом в обработке заявки добавлена эмуляция задержки, длительность которой в секундах передаётся в GET-параметре delay.

Из-за параллельного выполнения теоретически возможна ситуация, когда оба процесса получили разные заявки, но от одного пользователя, одновременно проверили наличие у него одобренных заявок, одновременно убедились, что таких заявок нет, и одновременно оба одобрили каждый свою заявку. В этом случае нарушается условие задачи про только одну одобренную заявку на пользователя. Для удобства тестирования данного аспекта задержка вставлена именно между проверкой одобренных заявок и обновлением статуса. Для решения этой проблемы было решено использовать блокировку строки пользователя текущей обрабатываемой заявки через `SELECT ... FOR UPDATE NOWAIT`. `FOR UPDATE` блокирует строку до завершения транзакции и не позволяет другому процессу получить блокировку этой же записи. А `NOWAIT` позволяет не блокировать выполнение другого процесса, который наткнулся на заблокированного пользователя, а сразу пропустить заявку и перейти к следующей. Таким образом несколько процессов обработки работают параллельно, их производительность кратна кол-ву запущенных процессов и не возникает конфликтов. Также на всякий случай обрабатывается вероятность Deadlock и Serialization failure - в этом случае производится повторное выполнение транзакции с той же заявкой.

## Установка

(предполагается, что вы используете Debian/Ubuntu-подобный Linux)

Клонируем репозиторий и запускаем докер-контейнеры:

```bash
git clone https://github.com/gugglegum/wiam-test.git
cd wiam-test
sudo docker-compose up -d
```

Заходим внутрь php-контейнера и производим в нём первичную настройку:

```bash
sudo docker exec -it php-container bash
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
apt install git
apt install unzip
composer install
php yii migrate
php yii user/create "john_doe" "john.doe@example.com" "secure_password123"
php yii user/create "john_smith" "john.smith@example.com" "secure_password321"
php yii user/create "jane_public" "jane.public@example.com" "secure_password312"
chmod -R 777 runtime
```

Так мы установили внутри php-контейнера Composer, git, unzip (нужен для composer), выполнили `composer install`, накатили миграции и создали 3 тестовых пользователя.

Далее выходим из докер-контейнера и через HTTP-запросы добавляем несколько тестовых заявок на кредиты:

```bash
curl -X POST -H "Content-Type: application/json" -d '{"user_id": 1, "amount": 3000, "term": 30}' http://localhost:8080/request
curl -X POST -H "Content-Type: application/json" -d '{"user_id": 2, "amount": 5000, "term": 60}' http://localhost:8080/request
curl -X POST -H "Content-Type: application/json" -d '{"user_id": 1, "amount": 4000, "term": 90}' http://localhost:8080/request
curl -X POST -H "Content-Type: application/json" -d '{"user_id": 2, "amount": 2000, "term": 45}' http://localhost:8080/request
```

### Тестирование

Для проверки текущего состояния заявок можем воспользоваться командой:
```bash
PGPASSWORD=wiam_password psql -U wiam_user -d WiamTest -h localhost -c "SELECT * FROM requests"
```

На начальном этапе у всех заявок должен быть status = 0.

Для проверки одновременного выполнения обработчика заявок открываем 2 терминала и почти одновременно в обоих терминалах запускаем команду:

```bash
curl -X GET "http://localhost:8080/processor?delay=5"
```
После их окончания смотрим лог в runtime/logs/app.log

Для сброса статуса всех заявок можно выполнить:
```bash
PGPASSWORD=wiam_password psql -U wiam_user -d WiamTest -h localhost -c "UPDATE requests SET status = 0"
```
