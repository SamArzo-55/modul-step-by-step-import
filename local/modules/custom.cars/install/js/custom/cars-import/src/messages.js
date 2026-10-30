export const MESSAGES = Object.freeze({
    dialog: {
        DialogTitle: 'Import cars from Excel',
        DialogSummary: 'Select an .xlsx file. The column titles must be in the first row. Large files are processed in portions.',
        DialogStartButton: 'Start import',
        DialogStopButton: 'Stop',
        DialogCloseButton: 'Close',
        RequestCanceling: 'Stopping...',
        RequestCanceled: 'The import was stopped.',
        RequestCompleted: 'The import is finished.',
    },
    fileTitle: 'Excel file (.xlsx)',
    fileRequired: 'Select an .xlsx file.',
    steps: {
        upload: ['Uploading the file...', 'Uploading'],
        index: ['Preparing the text values...', 'Preparing'],
        parse: ['Reading the rows...', 'Reading'],
        import: ['Creating the cars...', 'Importing'],
    },
});
