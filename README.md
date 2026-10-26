# Generador de preguntas con IA · AI question generator (`qbank_aigen`)

[![Tests](https://github.com/Alfredo455/moodle-qbank_aigen/actions/workflows/tests.yml/badge.svg)](https://github.com/Alfredo455/moodle-qbank_aigen/actions/workflows/tests.yml)
![Moodle 4.5](https://img.shields.io/badge/Moodle-4.5-f98012)
![Status beta](https://img.shields.io/badge/status-beta-yellow)
![AI: Moodle AI subsystem / OpenAI-compatible](https://img.shields.io/badge/AI-core__ai%20%7C%20OpenAI--compatible-8a2be2)

> **English summary:** a question bank plugin for Moodle 4.5 that generates multiple choice, true/false and
> short answer questions from pasted text or a course page using the new Moodle AI subsystem (or any
> OpenAI-compatible API, including self-hosted Ollama). Teachers review the questions before saving them;
> saved questions are created as tagged *drafts*, never published automatically.

Añade una pestaña **"Generador IA"** al banco de preguntas de Moodle: el profesor pega sus apuntes (o elige
una página del curso), la IA redacta las preguntas, **el profesor las revisa** y las que conserva se guardan
en el banco de preguntas listas para usar en cuestionarios.

## Funcionalidades

- **Tres tipos de pregunta**: opción múltiple, verdadero/falso y respuesta corta, con retroalimentación por
  respuesta y general.
- **Fuentes**: texto pegado o cualquier **página del curso**.
- **Opciones**: número de preguntas, dificultad (recordar, comprender, analizar), idioma (cualquiera de los
  instalados en Moodle) e instrucciones adicionales del profesor.
- **Revisión humana obligatoria**: las preguntas se muestran con la respuesta correcta marcada y el profesor
  elige cuáles conservar.
- **IA responsable**: las preguntas se guardan con estado **Borrador** y la etiqueta `ai-generated`, así no
  pueden usarse en un cuestionario hasta que alguien las revise.
- **Dos motores de IA**:
  - El **subsistema de IA de Moodle 4.5** (recomendado): usa los proveedores que configure el administrador
    (OpenAI, Azure OpenAI...), con registro de uso y aceptación de la política de IA por cada usuario.
  - Cualquier **API compatible con OpenAI**: OpenAI, Groq, Together, vLLM u **Ollama en tu propio servidor**,
    para que el contenido del curso no salga de la institución.
- **Robustez**: tolera respuestas con bloques markdown o texto alrededor del JSON, valida cada pregunta
  (por ejemplo, exactamente una opción correcta y sin opciones duplicadas) y descarta las inválidas sin perder el resto.
- **Control de costes**: límite de generaciones por usuario y hora, y longitud máxima del material.
- **Modo demo sin clave de API**: con `$CFG->qbank_aigen_demo = true;` en `config.php` se generan preguntas
  sencillas de completar huecos sin IA, para probar todo el flujo.

## Cómo funciona

```
Material del curso ──► prompt_builder ──► backend (core_ai | API OpenAI | demo) ──► response_parser
                                                                                         │
       Banco de preguntas ◄── question_creator (borrador + etiqueta) ◄── revisión del profesor
```

```
classes/plugin_feature.php, navigation.php  Integración en la navegación del banco de preguntas (API qbank)
classes/local/prompt_builder.php            Prompt con reglas por tipo y esquema JSON de ejemplo
classes/local/ai/                           Motores intercambiables detrás de una interfaz común
classes/local/response_parser.php           Extracción y validación tolerante del JSON
classes/local/question_creator.php          Creación de preguntas con la API de tipos de pregunta de Moodle
classes/local/drafts.php                    Lotes pendientes de revisión y límite por hora
classes/privacy/provider.php                API de privacidad (RGPD)
amd/src/                                    Estado de carga y selección múltiple en la revisión
```

## Instalación

```bash
git clone https://github.com/Alfredo455/moodle-qbank_aigen.git question/bank/aigen
```

1. Visita *Administración del sitio > Notificaciones*.
2. Configura la IA de una de estas dos formas:
   - **Subsistema de IA de Moodle**: en *Administración del sitio > General > IA > Proveedores de IA*, activa
     un proveedor (por ejemplo, OpenAI) con la acción **Generar texto**.
   - **API compatible con OpenAI**: en *Administración del sitio > Plugins > Plugins del banco de preguntas >
     Generador de preguntas con IA*, elige "API compatible con OpenAI" e indica el endpoint, la clave y el modelo.
3. En un curso, abre *Banco de preguntas > Generador IA*.

Requisitos: Moodle 4.5 LTS y PHP 8.1 a 8.3. Permiso `qbank/aigen:generate` (profesores con edición y gestores
por defecto) y `moodle/question:add`.

## Tests

- **PHPUnit**: construcción del prompt, extracción y validación de respuestas (JSON con markdown, texto
  alrededor, preguntas inválidas), el motor OpenAI con un cliente HTTP simulado (cuerpo de la petición,
  cabeceras, errores de la API), el flujo completo con un modelo falso, la creación de preguntas de los
  tres tipos (estado borrador, etiqueta, fracciones correctas, escapado de HTML), borradores, límite por
  hora, limpieza y privacidad.
- **Behat**: el flujo completo en la interfaz (generar desde una página del curso, deseleccionar una
  pregunta, guardar y verla en el banco), usando el motor demo.

En GitHub Actions hay dos trabajos: uno de calidad de código (lint, estándar de Moodle, PHPDoc, plantillas y
JavaScript) y otro con PHPUnit y Behat en PHP 8.2 + PostgreSQL y PHP 8.3 + MariaDB.

## Estado

Versión **0.9 (beta)**: el flujo completo funciona y está cubierto por tests, pero la calidad de las preguntas
depende del modelo de lenguaje que se use. Por eso las preguntas siempre se guardan como borrador para que el
profesor las revise.

## Privacidad

Al modelo de lenguaje solo se envía el material elegido por el profesor, nunca datos de estudiantes. Los
lotes sin revisar se guardan como máximo un día. Implementa la API de privacidad de Moodle.

## Licencia

GPL v3 o posterior, la misma licencia que Moodle. © 2026 [Alfredo](https://github.com/Alfredo455)
