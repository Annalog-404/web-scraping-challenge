from airflow import DAG
from datetime import datetime, timedelta
from airflow.operators.bash import BashOperator

with DAG(
    "web-scraping",

    default_args = {
        'owner': 'airflow',
        "depends_on_past": False,
        'start_date': datetime(2023, 3, 1),
        'retries': 1,
        catchup=False, 
        schedule_interval='@once'
    }

) as dag:


def web_scraping_challenge():
    os.chdir('/path/to/laravel/project')
    subprocess.call(['php', 'artisan', 'scraping:run'])

scraping_task = PythonOperator(
    task_id='scraping_task',
    python_callable=web_scraping_challenge,
    dag=dag,
)

scraping_task.set_upstream(start_task)
end_task.set_upstream(scraping_task)