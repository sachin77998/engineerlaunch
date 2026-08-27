<?php

return [[
    'title' => 'For Loop in Programming',
    'updated_at' => '26 March 2026',
    'summary' => 'A for loop repeats a block of code in a controlled way. Use it when the number of iterations is known, when traversing a sequence, or when generating a predictable series.',
    'visual' => 'for-loop-flow',
    'code' => "public class ForLoopExample {\n    public static void main(String[] args) {\n        for (int i = 1; i <= 5; i++) {\n            System.out.print(i + \" \" );\n        }\n        System.out.println();\n    }\n}\n\n// Output: 1 2 3 4 5",
    'task' => 'Change the loop to print the even numbers from 2 through 10. Then rewrite it as a for-each loop over an integer array.',
    'sections' => [
        ['title'=>'Basic for loop','text'=>'Initialization runs once, the condition is checked before every iteration, and the update runs after the loop body.','code'=>"for (int i = 2; i <= 10; i += 2) {\n    System.out.print(i + \" \" );\n}\n// 2 4 6 8 10"],
        ['title'=>'For-each loop','text'=>'Use for-each when you need every element and do not need its index.','code'=>"int[] numbers = {1, 2, 3, 4, 5};\nfor (int number : numbers) {\n    System.out.print(number + \" \" );\n}\n// 1 2 3 4 5"],
        ['title'=>'Multiple control variables','text'=>'Java permits more than one initializer and update expression. Keep the condition clear.','code'=>"for (int i = 0, j = 10; i < 5 && j > 0; i++, j--) {\n    System.out.println(\"i=\" + i + \", j=\" + j);\n}"],
        ['title'=>'Infinite for loop','text'=>'An omitted condition is always true. Provide a safe break, cancellation, or shutdown condition.','code'=>"for (;;) {\n    // Repeats until break, return, or process termination.\n}"],
        ['title'=>'Nested for loop','text'=>'The inner loop completes for each outer-loop iteration. This is useful for matrices and patterns.','code'=>"for (int row = 1; row <= 3; row++) {\n    for (int column = 1; column <= 3; column++) {\n        System.out.print(row * column + \" \" );\n    }\n    System.out.println();\n}"],
        ['title'=>'Step or stride','text'=>'Adjust the update expression to skip values or count backwards.','code'=>"for (int i = 0; i < 10; i += 2) {\n    System.out.print(i + \" \" );\n}\n// 0 2 4 6 8"],
    ],
    'options' => ['The condition is checked before each iteration','Initialization runs after every iteration','The update runs before the first condition check','A for loop must always be infinite'],
    'answer' => 0,
    'why' => 'In a conventional Java for loop, initialization runs once, then condition, body, and update repeat.',
]];
