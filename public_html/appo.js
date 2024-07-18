function copyToClipboard() {
    const dummy = document.createElement('textarea');
    document.body.appendChild(dummy);
    dummy.value = window.location.href;
    dummy.select();
    document.execCommand('copy');
    document.body.removeChild(dummy);
    alert('URLをコピーしました！ 参加者に共有すると出欠入力をしてもらうことが出来ます。');
}
