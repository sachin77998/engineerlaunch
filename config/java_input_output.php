<?php

$examples = [
    'java' => ['label' => 'Java', 'code' => "Scanner input = new Scanner(System.in);\nString name = input.nextLine();\nint age = input.nextInt();\ndouble height = input.nextDouble();\nchar grade = input.next().charAt(0);"],
    'cpp' => ['label' => 'C++', 'code' => "string name; int age; double height; char grade;\ngetline(cin, name);\ncin >> age >> height >> grade;"],
    'php' => ['label' => 'PHP', 'code' => '$name = trim(fgets(STDIN));'."\n".'$age = (int) trim(fgets(STDIN));'."\n".'$height = (float) trim(fgets(STDIN));'."\n".'$grade = trim(fgets(STDIN));'],
    'python' => ['label' => 'Python', 'code' => "name = input()\nage = int(input())\nheight = float(input())\ngrade = input()[0]"],
];

return [[
    'title' => 'Input and Output in Java',
    'summary' => 'Input lets a program receive data and output presents results. Java writes through System.out and commonly reads values through a Scanner connected to System.in.',
    'code' => <<<'JAVA'
import java.util.Scanner;
public class Main {
    public static void main(String[] args) {
        Scanner scanner = new Scanner(System.in);
        String name = scanner.nextLine();
        int age = scanner.nextInt();
        double height = scanner.nextDouble();
        char grade = scanner.next().charAt(0);
        System.out.println("----- Student Details -----");
        System.out.println("Name: " + name);
        System.out.println("Age: " + age);
        System.out.println("Height: " + height);
        System.out.println("Grade: " + grade);
        scanner.close();
    }
}
JAVA,
    'task' => 'Ask for a full name, city, age and favourite programming language, then print a formatted profile summary.',
    'options' => ['nextLine() reads a complete line, including spaces.','nextInt() reads every value as text.','print() always moves to a new line.','Scanner needs no import.'],
    'answer' => 0,
    'why' => 'nextLine() consumes a complete line, while next() stops at whitespace. println() adds a line break; print() does not.',
    'sections' => [
        ['title'=>'Display console output','body'=>'System.out.print() remains on the same line. System.out.println() prints and then starts a new line.'],
        ['title'=>'Read user input','body'=>'Import java.util.Scanner, connect it to System.in, then use the method matching the value type.'],
        ['title'=>'Common Scanner methods','body'=>'nextInt(): integer · nextLong(): long · nextDouble(): decimal · next(): word · nextLine(): line · next().charAt(0): character.'],
        ['title'=>'Important input rule','body'=>'After nextInt() or nextDouble(), consume the remaining newline before nextLine(), or it may appear to be skipped.'],
    ],
    'playground' => ['stdin'=>"Rahul Sharma\n22\n5.8\nA",'examples'=>$examples],
]];
