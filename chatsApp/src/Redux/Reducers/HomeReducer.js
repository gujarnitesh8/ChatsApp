import { GET_ALBUM_LIST_SUCCESS, GET_ALBUM_LIST_FAILED } from '../Types';


const INITIAL_STATE = {}

export default (state = INITIAL_STATE, action) => {
    switch (action.type) {

        case GET_ALBUM_LIST_SUCCESS:
            return { ...state, albumListSuccess: true, albumList: action.payload }

        case GET_ALBUM_LIST_FAILED:
            return { ...state, albumListSuccess: false, albumListFail: action.payload }

        default:
            return state;
    }
}