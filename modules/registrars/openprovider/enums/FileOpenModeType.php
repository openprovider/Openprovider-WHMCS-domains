<?php

namespace OpenProvider\WhmcsRegistrar\enums;

class FileOpenModeType
{
    const Read = 'r';
    const ReadPlus = 'r+';

    const Write = 'w';
    const WritePlus = 'w+';

    const CreateAndWrite = 'a';
    const CreateAndWritePlus = 'a+';
}