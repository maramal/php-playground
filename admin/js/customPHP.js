function createCustomPhpModel(value, monaco) {
    const fullValue = `<?php\n${value}`;
    const model = monaco.editor.createModel(fullValue, 'php');
    model.onDidChangeContent((event) => {
        const modelValue = model.getValue();
        const withoutPhpTag = modelValue.replace('<?php\n', '');
        document.querySelector("#code").value = withoutPhpTag;
    });
    return model;
}
