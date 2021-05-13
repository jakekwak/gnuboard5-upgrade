# 설명

이 곳은  [그누보드 원본](https://github.com/gnuboard/gnuboard5/tree/master)의 기능을 추가하고 있습니다.
원본에서 구현이 필요한 기능 또는 필요한데 따로 떨어져 있는 기능들을 포함해서 한번에 설치 및 테스트할 수 있습니다.

# 브랜치 설명

브랜치 [gnuboard5](https://github.com/jakekwak/gnuboard5/tree/gnuboard5)는  그누보드 원본과 동기화됩니다.
원본이 업데이트 되면,  gnuboard5도 같이 업데이트할 예정입니다.  커밋의 차이가 있으면 아직 업데이트가 안된 것입니다.
즉 그누보드 원본 커밋 발생이 되면 master에 새로 생긴 커밋을 업데이트한 후에, gnuboard5에 어디까지 적용되었는지 업데이트합니다.

# 설치

```
git clone https://github.com/jakekwak/gnuboard5 
```

* [x] data 디렉토리 포함. 
  * [ ] 리눅스에서 chmod로 변경은 해줘야 함.
* [x] 게시글을 드래그하여 정렬하기 <https://sir.kr/g5_plugin/8812>
* [x] SIR글 더미데이터 가져오기 <https://sir.kr/g5_plugin/8893>
* [x] FIREPHP Console 디버그 <https://sir.kr/g5_plugin/7938>
* [x] 그누보드 5.4 버전용 알림 플러그인 <https://sir.kr/g5_plugin/6259>
* [x] 마크다운을 위한 Theme: vditor-basic 구현
   * [x] mp4 파일 지원
   * [x] webp, avif 파일 지원
   * [x] wav, mp3 파일 지원
   * [x] 브라우저에서 녹음테스트 (https에서만 지원됨)*