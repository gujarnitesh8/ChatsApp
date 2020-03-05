import { put, call, takeEvery } from 'redux-saga/effects'

import Api from '../Services/api';
import {
    GET_ALBUM_LIST_REQUEST,
    GET_ALBUM_LIST_FAILED,
    GET_ALBUM_LIST_SUCCESS
} from '../Types';



// ======>>>>>>> GET ALBUM LIST SAGA <<<<<<<<==========
export const getAlbumListSaga = function* getAlbumListSaga({ params }) {
    try {
        const response = yield call(Api.getAlbumList, params)
        yield put({ type: GET_ALBUM_LIST_SUCCESS, payload: response });
    }
    catch (e) {
        yield put({ type: GET_ALBUM_LIST_FAILED, payload: e });
    }
}

export function* authSaga() {
    yield takeEvery(GET_ALBUM_LIST_REQUEST, getAlbumListSaga);
}
export default authSaga;