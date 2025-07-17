# Bulk Question Upload CSV Format

This document describes the required format for the CSV file used to bulk upload multiple-choice questions.

## File Encoding

The CSV file must be UTF-8 encoded.

## Header Row

The first row of the CSV file **must** be a header row and contain the following columns in the exact order:

`exam_subject_id,topic_id,question_text,correct_answer,option_A,option_B,option_C,option_D,explanation,difficulty_level`

## Column Descriptions

*   **`exam_subject_id`** (Required, Integer): The ID of the exam subject this question belongs to.
*   **`topic_id`** (Optional, Integer): The ID of the topic this question belongs to. Can be left empty if no specific topic.
*   **`question_text`** (Required, String): The full text of the question.
*   **`correct_answer`** (Required, String): The letter corresponding to the correct option (e.g., "A", "B", "C", "D"). This must match one of the `option_` letters provided.
*   **`option_A`** (Required, String): The text for option A.
*   **`option_B`** (Required, String): The text for option B.
*   **`option_C`** (Required, String): The text for option C.
*   **`option_D`** (Required, String): The text for option D.
*   **`explanation`** (Optional, String): An explanation for the correct answer. Can be left empty.
*   **`difficulty_level`** (Optional, String): The difficulty level of the question (e.g., "Easy", "Medium", "Hard"). Can be left empty.

## Example CSV

```csv
exam_subject_id,topic_id,question_text,correct_answer,option_A,option_B,option_C,option_D,explanation,difficulty_level
1,101,"What is the capital of France?",A,"Paris","London","Berlin","Rome","Paris is the capital of France.","Easy"
1,,Who painted the Mona Lisa?,B,"Vincent van Gogh","Leonardo da Vinci","Pablo Picasso","Claude Monet",,"Medium"
2,201,"Which planet is known as the Red Planet?",C,"Earth","Venus","Mars","Jupiter","Mars is often called the Red Planet.","Easy"
```

## Important Notes

*   Each row after the header represents a single multiple-choice question.
*   Ensure that the `correct_answer` column contains a letter that corresponds to one of the provided `option_` columns.
*   Do not include extra columns beyond `difficulty_level`.
*   Empty optional fields should be left blank (e.g., `,,` for `topic_id` and `explanation`).
*   The system currently only supports multiple-choice questions via bulk upload.
